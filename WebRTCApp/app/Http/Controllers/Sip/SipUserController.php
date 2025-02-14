<?php

namespace App\Http\Controllers\Sip;

use App\Http\Controllers\Controller;
use App\Models\Sip\SipAccount;
use App\Models\Sip\SipAor;
use App\Models\Sip\SipAuth;
use App\Models\Sip\SipEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SipUserController extends Controller
{
    public function store(Request $request) {

        DB::connection('mysql')->transaction(function () use ($request){

            $sipAccount = SipAccount::create([
                'user_id' => $request->user_id,
                'sip_user_id' => $request->sip_id,
                'password' => $request->password
            ]);
            
        });

         // Crear en base de datos Asterisk
         DB::connection('asterisk')->transaction(function () use ($request) {
            SipAor::create([
                'id' => $request->sip_id,
                'max_contacts' => $request->max_contacts,
                'qualify_frequency' => $request->qualify_frequency
            ]);

            SipAuth::create([
                'id' => $request->sip_id,
                'auth_type' => 'userpass',
                'password' => $request->password,
                'username' => $request->sip_id
            ]);

            SipEndpoint::create([
                'id' => $request->sip_id,
                'transport' => 'transport-wss',
                'aors' => $request->sip_id,
                'auth' => $request->sip_id,
                'context' => 'from-internal',
                'disallow' => 'all',
                'allow' => implode(',', $request->codecs),
                'direct_media' => $request->direct_media ? 'yes' : 'no',
                'deny' => '0.0.0.0/0',
                'permit' => '0.0.0.0/0',
                'mailboxes' => $request->mailboxes
            ]);
        });
        return redirect()->back()->with('success', 'SIP account created successfully');
    }
}
