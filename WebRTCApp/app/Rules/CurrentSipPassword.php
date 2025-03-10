<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use App\Models\Sip\SipAccount;
use App\Models\Sip\SipAuth;
use Illuminate\Support\Facades\Hash;

class CurrentSipPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        $sipUser = SipAccount::where('user_id', $user->id)->first();

        if (!$sipUser) {
            $fail('No se encontró una cuenta SIP asociada a este usuario.');
            return;
        }

        $sipAuth = SipAuth::where('id', $sipUser->sip_user_id)->first();

        if (!$sipAuth || md5($value) !== $sipAuth->password) { // 👈 Compare MD5 hashes
            $fail('La contraseña SIP actual es incorrecta.');
        }
    }
}
