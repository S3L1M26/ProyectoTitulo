<?php

namespace App\Models\Sip;

use Illuminate\Database\Eloquent\Model;

class SipAor extends Model
{
    protected $connection = 'asterisk';

    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'ps_aors';

    protected $fillable = [
        'id',             
        'max_contacts',
        'qualify_frequency',
    ];

    protected $keytype = 'string';
}
