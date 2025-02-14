<?php

namespace App\Models\Sip;

use Illuminate\Database\Eloquent\Model;

class SipEndpoint extends Model
{
    protected $connection = 'asterisk';

    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'ps_endpoints';

    protected $fillable = [
        'id',
        'transport',
        'aors',
        'auth',
        'context',
        'disallow',
        'allow',
        'direct_media',
        'deny',
        'permit',
        'mailboxes',
    ];

    protected $keyType = 'int';
}
