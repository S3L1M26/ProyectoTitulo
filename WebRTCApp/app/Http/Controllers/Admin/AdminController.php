<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\Sip\SipAccount;
use App\Models\Sip\SipAor;
use App\Models\Sip\SipEndpoint;
use App\Models\Sip\SipAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log; // Add this line

class AdminController extends Controller
{
    public function index(): Response
    {
        $users = User::where('role', '!=', 'admin')->whereDoesntHave('sipAccount')->get();

        $allUsersHaveSipAccount = $users->isEmpty();

        return Inertia::render('Admin/Dashboard/Index', [
            'users' => $users,
            'allUsersHaveSipAccount' => $allUsersHaveSipAccount
        ]);
    }

    public function users() {
        $users = User::where('role', '!=', 'admin')->get();
        $sipUsers = SipAccount::with('user')->get();
        $ps_aors = SipAor::select('id', 'max_contacts', 'qualify_frequency')->get();
        $ps_endpoints = SipEndpoint::select('id', 'allow', 'direct_media', 'mailboxes')->get();

        return Inertia::render('Admin/Dashboard/Users', [
            'users' => $users,
            'sipUsers' => $sipUsers,
            'ps_aors' => $ps_aors,
            'ps_endpoints' => $ps_endpoints
        ]);
    }

    public function editUser($id) {
        $user = User::find($id);
        $sipUser = SipAccount::with('user')->where('user_id', $user->id)->first();
        $ps_aor = SipAor::where('id', $sipUser->sip_user_id)->first();
        $ps_endpoint = SipEndpoint::where('id', $sipUser->sip_user_id)->first();
        return Inertia::render('Admin/Dashboard/Edit', [
            'user' => $user,
            'sipUser' => $sipUser,
            'ps_aor' => $ps_aor,
            'ps_endpoint' => $ps_endpoint
        ]);
    }

    public function updateUser(Request $request): RedirectResponse {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
            'max_contacts' => ['required', 'integer', 'min:1'],
            'qualify_frequency' => ['required', 'integer', 'min:10'],
            'allow' => ['required', 'string'], // Aceptar cadena de codecs
            'direct_media' => ['required', 'string', 'in:yes,no'], // Aceptar solo 'yes' o 'no'
            'mailboxes' => ['required', 'string']
        ]);
    
        $user = User::find($validated['id']);
        $sipUser = SipAccount::with('user')->where('user_id', $user->id)->first();
    
        if (!$sipUser) {
            return Redirect::route('admin.users')->withErrors(['sipUser' => 'SIP User not found']);
        }

    //     // Actualizar ps_endpoints
    //     DB::connection('asterisk')
    //     ->table('ps_endpoints')
    //     ->where('id', $sipUser->sip_user_id) // ✅ ID correcto
    //     ->update([
    //         'allow' => $validated['allow'],
    //         'direct_media' => $validated['direct_media'],
    //         'mailboxes' => $validated['mailboxes']
    //     ]);

    // // Actualizar ps_aors usando el ID dinámico
    //      DB::connection('asterisk')
    //     ->table('ps_aors')
    //     ->where('id', $sipUser->sip_user_id) // ✅ ID correcto
    //     ->update([
    //         'max_contacts' => $validated['max_contacts'],
    //         'qualify_frequency' => $validated['qualify_frequency']
    //     ]);
    
        DB::connection('asterisk')->transaction(function() use ($sipUser, $validated) {
            Log::info('Updating SipEndpoint:', [
                'id' => $sipUser->sip_user_id,
                'data' => [
                    'allow' => $validated['allow'],
                    'direct_media' => $validated['direct_media'],
                    'mailboxes' => $validated['mailboxes']
                ]
            ]);
        
            SipEndpoint::where('id', $sipUser->sip_user_id)->update([
                'allow' => $validated['allow'],
                'direct_media' => $validated['direct_media'],
                'mailboxes' => $validated['mailboxes']
            ]);

            SipAor::where('id', $sipUser->sip_user_id)->update([
                'max_contacts' => $validated['max_contacts'],
                'qualify_frequency' => $validated['qualify_frequency']
            ]);
        });

        return Redirect::route('admin.users')->with('success', 'User updated successfully');
    }

    public function destroyUser(Request $request) {

        $request->validate([
            'password' => ['required', 'current_password'],
            'user_id' => ['required', 'integer', 'exists:users,id']
        ]);

        $user = User::find($request->id);
        $sipUser = SipAccount::with('user')->where('user_id', $user->id)->first();

        if($sipUser){

            DB::connection('asterisk')->transaction(function() use ($sipUser) {
                SipAor::where('id', $sipUser->sip_user_id)->delete();
                SipAuth::where('id', $sipUser->sip_user_id)->delete();
                SipEndpoint::where('id', $sipUser->sip_user_id)->delete();
            });

            $sipUser->delete();
        }

        $user->delete();

    }
}
