<?php

namespace App\Models\Sip;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SipAccount extends Model
{
    protected $fillable = ['user_id', 'sip_user_id', 'password'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aor()
    {
        return $this->hasOne(SipAor::class, 'id', 'sip_user_id');
    }

    public function auth()
    {
        return $this->hasOne(SipAuth::class, 'id', 'sip_user_id');
    }

    public function endpoint()
    {
        return $this->hasOne(SipEndpoint::class, 'id', 'sip_user_id');
    }
}
