<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos existentes de especialidad a relación many-to-many
        $mentors = DB::table('mentors')->get();
        
        foreach ($mentors as $mentor) {
            if (!empty($mentor->especialidad)) {
                // Buscar o crear área de interés basada en especialidad
                $areaInteres = DB::table('areas_interes')
                    ->where('nombre', 'like', '%' . $mentor->especialidad . '%')
                    ->first();
                
                if (!$areaInteres) {
                    // Crear área de interés si no existe
                    $areaInteresId = DB::table('areas_interes')->insertGetId([
                        'nombre' => $mentor->especialidad,
                        'descripcion' => 'Área especializada: ' . $mentor->especialidad,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $areaInteresId = $areaInteres->id;
                }
                
                // Crear relación en tabla pivot
                DB::table('mentor_area_interes')->insertOrIgnore([
                    'mentor_id' => $mentor->id,
                    'area_interes_id' => $areaInteresId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Eliminar columna especialidad
        Schema::table('mentors', function (Blueprint $table) {
            $table->dropColumn('especialidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->string('especialidad')->nullable()->after('experiencia');
        });
        
        // Restaurar datos de especialidad desde relaciones
        $mentors = DB::table('mentors')->get();
        
        foreach ($mentors as $mentor) {
            $primeraEspecialidad = DB::table('mentor_area_interes')
                ->join('areas_interes', 'mentor_area_interes.area_interes_id', '=', 'areas_interes.id')
                ->where('mentor_area_interes.mentor_id', $mentor->id)
                ->value('areas_interes.nombre');
                
            if ($primeraEspecialidad) {
                DB::table('mentors')
                    ->where('id', $mentor->id)
                    ->update(['especialidad' => $primeraEspecialidad]);
            }
        }
    }
};