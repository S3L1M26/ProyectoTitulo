<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Sip\SipAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index() //Response
    {
        $user = Auth::user();

        return Inertia::render('Dashboard/Index', [
            'sip_account' => SipAccount::with('user')->where('user_id', $user->id)->first(),
        ]);
    }
}
