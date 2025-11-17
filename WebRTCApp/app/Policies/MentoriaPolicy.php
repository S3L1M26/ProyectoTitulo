<?php

namespace App\Policies;

use App\Models\Mentoria;
use App\Models\SolicitudMentoria;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MentoriaPolicy
{
    /**
     * Determina si el usuario puede confirmar la mentoría para la solicitud dada.
     */
    public function confirmar(User $user, SolicitudMentoria $solicitud): bool
    {
        Log::info('🔐 POLICY CHECK - MentoriaPolicy::confirmar()', [
            'user_id' => $user->id,
            'solicitud_id' => $solicitud->id,
            'solicitud_mentor_id' => $solicitud->mentor_id,
            'solicitud_estado' => $solicitud->estado,
            'tiene_mentoria_programada' => $solicitud->tieneMentoriaProgramada(),
        ]);

        // Debe ser mentor relacionado a la solicitud y la solicitud debe estar aceptada o pendiente sin mentoría programada.
        if ($solicitud->mentor_id !== $user->id) {
            Log::warning('❌ POLICY DENIED: mentor_id no coincide', [
                'expected' => $solicitud->mentor_id,
                'actual' => $user->id,
            ]);
            return false;
        }

        // Solo puede confirmar si la solicitud está aceptada o pendiente y aún no tiene mentoría.
        if ($solicitud->tieneMentoriaProgramada()) {
            Log::warning('❌ POLICY DENIED: Ya tiene mentoría programada');
            return false;
        }

        $allowed = in_array($solicitud->estado, ['aceptada', 'pendiente', 'cancelada']);
        if (!$allowed) {
            Log::warning('❌ POLICY DENIED: Estado no permitido', [
                'estado_actual' => $solicitud->estado,
                'estados_permitidos' => ['aceptada', 'pendiente', 'cancelada'],
            ]);
        } else {
            Log::info('✅ POLICY ALLOWED: Puede confirmar mentoría');
        }

        return $allowed;
    }

    /**
     * Determina si el usuario puede unirse a la mentoría.
     */
    public function unirse(User $user, Mentoria $mentoria): bool
    {
        // Puede unirse si es el mentor o el aprendiz asociado
        if ($user->id === $mentoria->mentor_id || $user->id === $mentoria->aprendiz_id) {
            // Además validar que la mentoría no esté cancelada y esté en ventana de unión
            if ($mentoria->estado === 'cancelada') {
                return false;
            }
            return true;
        }
        return false;
    }
}
