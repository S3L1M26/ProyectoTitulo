<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Sip\SipAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MentorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $sip_account = SipAccount::with('user')->where('user_id', $user->id)->first();
        $password = $sip_account ? decrypt($sip_account->password) : null;

        return Inertia::render('Mentor/Dashboard/Index', [
            'sip_account' => $sip_account,
            'password' => $password,
        ]);
    }
}
