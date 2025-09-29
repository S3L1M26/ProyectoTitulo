<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AreaInteres extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'areas_interes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * The students that belong to this area of interest.
     */
    public function aprendices()
    {
        return $this->belongsToMany(Aprendiz::class, 'aprendiz_area_interes');
    }
}
