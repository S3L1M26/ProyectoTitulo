<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sip\SipAccount;
use App\Models\Sip\SipAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Rules\CurrentSipPassword;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }

    public function updateSipPassword(Request $request): RedirectResponse 
    {
        $request->validate([
            'current_sip_password' => ['required', new CurrentSipPassword],
            'new_sip_password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $sipUser = SipAccount::with('user')->where('user_id', $user->id)->first();

        if($sipUser){
            $newSipPassword = $request->input('new_sip_password');
            $hashedPassword = Hash::make($newSipPassword);

            $sipUser->password = $hashedPassword;
            $sipUser->save();

            DB::connection('asterisk')->transaction(function() use ($sipUser, $newSipPassword) {
                SipAuth::where('id', $sipUser->sip_user_id)->update(['password' => $newSipPassword]);
            });

            return Redirect::route('profile.edit')->with('status', 'Clave SIP actualizada.');
        }
        return Redirect::route('profile.edit')->with('error', 'No se encontró una cuenta SIP asociada a este usuario.');
    }
}
