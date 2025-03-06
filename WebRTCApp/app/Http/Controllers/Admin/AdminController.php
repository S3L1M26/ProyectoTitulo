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
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Hash;

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
        Log::info('Entering updateUser method');
        Log::info('Request data:', $request->all());
        
        // Convertir "allow" a string si es un array
        if (is_array($request->allow)) {
            $request->merge([
                'allow' => implode(',', $request->allow), // 👈 Convertir array a string
            ]);
        }

        Log::info('Processed Request data:', $request->all());

        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:users,id'],
                'max_contacts' => ['required', 'integer', 'min:1'],
                'qualify_frequency' => ['required', 'integer', 'min:10'],
                'allow' => ['required', 'string'],
                'direct_media' => ['required', 'string', 'in:yes,no'],
                'mailboxes' => ['required', 'string']
            ]);
    
            Log::info('Validation passed', ['validated_data' => $validated]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return Redirect::route('admin.users')->withErrors($e->errors());
        }
    
        Log::info('Validation passed', ['validated_data' => $validated]);
    
        $user = User::find($validated['id']);
        Log::info('User found', ['user' => $user]);
    
        $sipUser = SipAccount::with('user')->where('user_id', $user->id)->first();
        Log::info('SIP User query executed', ['sipUser' => $sipUser]);
    
        if (!$sipUser) {
            Log::info('SIP User not found', ['user_id' => $validated['id']]);
            return Redirect::route('admin.users')->withErrors(['sipUser' => 'SIP User not found']);
        }
    
        Log::info('Updating SIP User:', [
            'sip_user_id' => $sipUser->sip_user_id,
            'validated_data' => $validated
        ]);
    
        try {
            DB::connection('asterisk')->transaction(function() use ($sipUser, $validated) {
                Log::info('Updating SipEndpoint:', [
                    'id' => (string) $sipUser->sip_user_id,
                    'data' => [
                        'allow' => $validated['allow'],
                        'direct_media' => $validated['direct_media'],
                        'mailboxes' => $validated['mailboxes']
                    ]
                ]);
    
                SipEndpoint::where('id', (string) $sipUser->sip_user_id)->update([
                    'allow' => $validated['allow'],
                    'direct_media' => $validated['direct_media'],
                    'mailboxes' => $validated['mailboxes']
                ]);
    
                Log::info('Updating SipAor:', [
                    'id' => (string) $sipUser->sip_user_id,
                    'data' => [
                        'max_contacts' => $validated['max_contacts'],
                        'qualify_frequency' => $validated['qualify_frequency']
                    ]
                ]);
    
                SipAor::where('id', (string) $sipUser->sip_user_id)->update([
                    'max_contacts' => $validated['max_contacts'],
                    'qualify_frequency' => $validated['qualify_frequency']
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error updating SIP User', ['exception' => $e->getMessage()]);
            return Redirect::route('admin.users')->withErrors(['error' => 'Failed to update SIP User']);
        }
    
        return Redirect::route('admin.users')->with('success', 'User updated successfully');
    }

    public function resetPassword(Request $request, $id): RedirectResponse {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = User::find($id);
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        return back();
    }

    public function resetSipPassword(Request $request, $id): RedirectResponse {
        $request->validate([
            'new_sip_password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $sipUser = SipAccount::with('user')->where('sip_user_id', $id)->first();

        if($sipUser){
            $newSipPassword = $request->input('new_sip_password');
            $hashedPassword = Hash::make($newSipPassword);

            $sipUser->password = $hashedPassword;
            $sipUser->save();

            DB::connection('asterisk')->transaction(function() use ($sipUser, $newSipPassword) {
                SipAuth::where('id', $sipUser->sip_user_id)->update(['password' => $newSipPassword]);
            });

            return back()->with('success', 'SIP password updated successfully');
        }
        return back()->withErrors(['error' => 'Failed to update SIP password']);
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
