import React from 'react';

export default function MentoriaCard({ mentoria, userRole = 'mentor' }) {
    if (!mentoria) return null;

    const fecha = new Date(mentoria.fecha);
    const hora = new Date(mentoria.hora);
    const nombreOtro = userRole === 'mentor' ? (mentoria?.aprendiz?.name || 'Aprendiz') : (mentoria?.mentor?.name || 'Mentor');
    const estado = mentoria.estado;

    const badgeClass = estado === 'confirmada'
        ? 'bg-green-100 text-green-800'
        : estado === 'cancelada'
            ? 'bg-red-100 text-red-800'
            : 'bg-gray-100 text-gray-800';

    const joinLabel = userRole === 'mentor' ? 'Iniciar reunión' : 'Unirse a la reunión';

    return (
        <div className="border rounded-lg p-5 bg-white hover:shadow transition-shadow">
            <div className="flex items-start justify-between mb-3">
                <h4 className="font-semibold text-gray-900">{nombreOtro}</h4>
                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${badgeClass}`}>
                    {estado}
                </span>
            </div>
            <div className="text-sm text-gray-700 space-y-1">
                <p>
                    <strong>Fecha:</strong> {fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })}
                </p>
                <p>
                    <strong>Hora:</strong> {hora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}
                </p>
                {mentoria?.area?.nombre && (
                    <p>
                        <strong>Área:</strong> {mentoria.area.nombre}
                    </p>
                )}
                <p>
                    <strong>Duración:</strong> {mentoria.duracion_minutos} min
                </p>
            </div>
            {mentoria.enlace_reunion && estado !== 'cancelada' && (
                <a
                    href={route('mentorias.unirse', mentoria.id)}
                    className="mt-4 inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                >
                    🎥 {joinLabel}
                </a>
            )}
        </div>
    );
}
