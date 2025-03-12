<?php

namespace App\Http\Controllers\Sip;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sip\SipAccount;
use App\Models\Sip\SipAor;
use App\Models\Sip\SipAuth;
use App\Models\Sip\SipEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;


class SipUserController extends Controller
{
    public function store(Request $request) {

        if(User::whereDoesntHave('sipAccount')->count() === 0){
            return redirect()->back()->with('error', 'All users already have a SIP account');
        }

        $validated = $this->validateRequest($request);

        DB::connection('mysql')->transaction(function () use ($validated){

            SipAccount::create([
                'user_id' => $validated['user_id'],
                'sip_user_id' => (int) $validated['sip_id'],
                'password' => Crypt::encryptString($validated['password']),
            ]);
            
        });

        // Crear en base de datos Asterisk
        DB::connection('asterisk')->transaction(function () use ($validated) {
            $sipIdString = (string) $validated['sip_id'];

            SipAor::create([
                'id' => $sipIdString,
                'max_contacts' => $validated['max_contacts'],
                'qualify_frequency' => $validated['qualify_frequency']
            ]);

            SipAuth::create([
                'id' => $sipIdString,
                'auth_type' => 'md5',
                'md5_cred' => md5("{$sipIdString}:webrtc.connect360.cl:{$validated['password']}"), //105:webrtc.connect360.cl:ContrasenaRobusta
                'username' => $sipIdString,
                'realm' => 'webrtc.connect360.cl'
            ]);

            SipEndpoint::create(array_merge(
                [
                    'id' => $sipIdString,
                    'transport' => 'transport-wss',
                    'aors' => $sipIdString,
                    'auth' => $sipIdString,
                    'context' => 'from-internal',
                    'disallow' => 'all',
                    'allow' => implode(',', $validated['codecs']),
                    'direct_media' => $validated['direct_media'] ? 'yes' : 'no',
                    'deny' => '0.0.0.0/0',
                    'permit' => '0.0.0.0/0',
                    'mailboxes' => $validated['mailboxes'] ?? null,
                ],
                SipEndpoint::getDefaultValues() //Valores predeterminados
            ));
        });
        return redirect()->back()->with('success', 'SIP account created successfully');
    }

    protected function validateRequest(Request $request){

        return $request->validate([
            'user_id' => 'required|exists:users,id|unique:sip_accounts,user_id',
            'sip_id' => [
                'required',
                'numeric',
                'unique:sip_accounts,sip_user_id',
                Rule::unique('asterisk.ps_aors', 'id'),
                Rule::unique('asterisk.ps_auths', 'id'),
                Rule::unique('asterisk.ps_endpoints', 'id'),
                function ($value, $fail) {
                    $existInApp = SipAccount::where('sip_user_id', $value)->exists();
                    $existInAsterisk = SipAor::where('id', $value)->exists() || SipAuth::where('id', $value)->exists() || SipEndpoint::where('id', $value)->exists();

                    if($existInApp || $existInAsterisk){
                        $fail('El id SIP está en uso');
                    }
                }
                
            ],
            'password' => 'required|min:8|max:64',
            'max_contacts' => 'required|numeric|min:1|max:5',
            'qualify_frequency' => 'required|numeric|min:10|max:300',
            'codecs' => 'required|array|min:1',
            'direct_media' => 'required|boolean',
            'mailboxes' => 'nullable|string',
        ]);
    }
}
