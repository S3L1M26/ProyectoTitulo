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
use Illuminate\Support\Facades\DB;

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

    public function updateUser() {

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
