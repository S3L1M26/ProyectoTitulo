import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'react-toastify';

export default function MentoriaCard({ mentoria, userRole = 'mentor' }) {
    const [showCancelModal, setShowCancelModal] = useState(false);
    const [cancelling, setCancelling] = useState(false);

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

    const handleCancelMentoria = () => {
        setCancelling(true);
        router.delete(route('mentor.mentorias.cancelar', mentoria.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Mentoría cancelada exitosamente.');
                setShowCancelModal(false);
            },
            onError: (errors) => {
                toast.error(errors.message || 'No se pudo cancelar la mentoría.');
            },
            onFinish: () => {
                setCancelling(false);
            },
        });
    };

    return (
        <>
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
                <div className="mt-4 space-y-2">
                    <a
                        href={route('mentorias.unirse', mentoria.id)}
                        className="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    >
                        🎥 {joinLabel}
                    </a>
                    {userRole === 'mentor' && (
                        <button
                            type="button"
                            onClick={() => setShowCancelModal(true)}
                            className="inline-flex items-center justify-center w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                        >
                            ❌ Cancelar Mentoría
                        </button>
                    )}
                </div>
            )}
        </div>

            {/* Modal de confirmación */}
            {showCancelModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div className="bg-white rounded-lg shadow-xl p-6 max-w-md mx-4">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            ¿Cancelar mentoría?
                        </h3>
                        <p className="text-gray-600 mb-6">
                            ¿Estás seguro de que deseas cancelar esta mentoría con <strong>{nombreOtro}</strong>?
                            Esta acción eliminará la reunión de Zoom y notificará al aprendiz.
                        </p>
                        <div className="flex justify-end space-x-3">
                            <button
                                type="button"
                                onClick={() => setShowCancelModal(false)}
                                className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors"
                                disabled={cancelling}
                            >
                                No, mantener
                            </button>
                            <button
                                type="button"
                                onClick={handleCancelMentoria}
                                className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                                disabled={cancelling}
                            >
                                {cancelling ? 'Cancelando...' : 'Sí, cancelar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
