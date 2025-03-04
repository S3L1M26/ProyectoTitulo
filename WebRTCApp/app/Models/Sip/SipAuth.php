<?php

namespace App\Models\Sip;

use Illuminate\Database\Eloquent\Model;

class SipAuth extends Model
{
    protected $connection = 'asterisk';

    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'ps_auths';

    protected $fillable = [
        'id',
        'auth_type',
        'password',
        'username',
    ];

    protected $keyType = 'string';
}
