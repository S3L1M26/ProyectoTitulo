<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use App\Models\Sip\SipAccount;
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

        if (!Hash::check($value, $sipUser->password)) {
            $fail('La contraseña SIP actual es incorrecta.');
        }
    }
}
