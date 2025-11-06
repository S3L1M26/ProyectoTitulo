<?php

namespace App\Policies;

use App\Models\Mentoria;
use App\Models\Models\SolicitudMentoria;
use App\Models\User;

class MentoriaPolicy
{
    /**
     * Determina si el usuario puede confirmar la mentoría para la solicitud dada.
     */
    public function confirmar(User $user, SolicitudMentoria $solicitud): bool
    {
        // Debe ser mentor relacionado a la solicitud y la solicitud debe estar aceptada o pendiente sin mentoría programada.
        if ($solicitud->mentor_id !== $user->id) {
            return false;
        }

        // Solo puede confirmar si la solicitud está aceptada o pendiente y aún no tiene mentoría.
        if ($solicitud->tieneMentoriaProgramada()) {
            return false;
        }

        return in_array($solicitud->estado, ['aceptada', 'pendiente']);
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
