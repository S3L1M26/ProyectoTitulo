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
