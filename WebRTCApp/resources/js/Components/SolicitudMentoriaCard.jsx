import React from 'react';
import { useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';

export default function SolicitudMentoriaCard({ solicitud, showActions = true, onAcceptClick }) {
    const { post, processing } = useForm();

    const handleAccept = () => {
        if (onAcceptClick) {
            onAcceptClick();
            return;
        }
        if (confirm('¿Estás seguro de que quieres aceptar esta solicitud?')) {
            post(route('mentor.solicitudes.accept', solicitud.id), {
                preserveScroll: true,
            });
        }
    };

    const handleReject = () => {
        if (confirm('¿Estás seguro de que quieres rechazar esta solicitud?')) {
            post(route('mentor.solicitudes.reject', solicitud.id), {
                preserveScroll: true,
            });
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <div className="border rounded-lg p-5 hover:shadow-lg transition-shadow bg-white">
            {/* Header con avatar y datos básicos */}
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center space-x-3">
                    <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md">
                        {solicitud.estudiante?.name?.charAt(0).toUpperCase() || 'E'}
                    </div>
                    <div>
                        <h4 className="font-semibold text-gray-900 text-lg">
                            {solicitud.estudiante?.name || 'Estudiante'}
                        </h4>
                        <p className="text-sm text-gray-600">
                            {solicitud.estudiante?.email}
                        </p>
                    </div>
                </div>

                {/* Badge de estado */}
                {!showActions && (
                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                        solicitud.estado === 'aceptada' 
                            ? 'bg-green-100 text-green-800' 
                            : solicitud.estado === 'rechazada'
                            ? 'bg-red-100 text-red-800'
                            : solicitud.estado === 'cancelada'
                            ? 'bg-orange-100 text-orange-800'
                            : 'bg-yellow-100 text-yellow-800'
                    }`}>
                        {solicitud.estado === 'aceptada' ? 'Aceptada' : 
                         solicitud.estado === 'rechazada' ? 'Rechazada' : 
                         solicitud.estado === 'cancelada' ? 'Cancelada' : 'Pendiente'}
                    </span>
                )}
            </div>

            {/* Información del estudiante */}
            {solicitud.aprendiz && (
                <div className="mb-4 p-3 bg-gray-50 rounded-lg">
                    <h5 className="text-sm font-semibold text-gray-700 mb-2">Información Académica</h5>
                    <div className="space-y-1 text-sm">
                        <p className="text-gray-700">
                            <span className="font-medium">Semestre:</span> {solicitud.aprendiz.semestre}°
                        </p>
                        {solicitud.aprendiz.objetivos && (
                            <p className="text-gray-700">
                                <span className="font-medium">Objetivos:</span> {solicitud.aprendiz.objetivos}
                            </p>
                        )}
                    </div>

                    {/* Áreas de interés */}
                    {solicitud.aprendiz.areas_interes && solicitud.aprendiz.areas_interes.length > 0 && (
                        <div className="mt-3">
                            <p className="text-sm font-medium text-gray-700 mb-2">Áreas de Interés:</p>
                            <div className="flex flex-wrap gap-2">
                                {solicitud.aprendiz.areas_interes.map((area) => (
                                    <span 
                                        key={area.id} 
                                        className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                                    >
                                        {area.nombre}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Mensaje del estudiante */}
            {solicitud.mensaje && (
                <div className="mb-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                    <p className="text-sm font-medium text-blue-900 mb-1 flex items-center">
                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        Mensaje:
                    </p>
                    <p className="text-sm text-blue-800">{solicitud.mensaje}</p>
                </div>
            )}

            {/* Fechas */}
            <div className="flex flex-wrap gap-4 text-xs text-gray-500 mb-4">
                <div className="flex items-center">
                    <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Solicitado: {formatDate(solicitud.fecha_solicitud)}</span>
                </div>
                {solicitud.fecha_respuesta && (
                    <div className="flex items-center">
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Respondido: {formatDate(solicitud.fecha_respuesta)}</span>
                    </div>
                )}
            </div>

            {/* Botones de acción */}
            {showActions && solicitud.estado === 'pendiente' && (
                <div className="flex gap-3 pt-3 border-t border-gray-200">
                    <PrimaryButton
                        onClick={handleAccept}
                        disabled={processing}
                        className="flex-1 justify-center"
                    >
                        {processing ? 'Procesando...' : 'Aceptar'}
                    </PrimaryButton>
                    <DangerButton
                        onClick={handleReject}
                        disabled={processing}
                        className="flex-1 justify-center"
                    >
                        {processing ? 'Procesando...' : 'Rechazar'}
                    </DangerButton>
                </div>
            )}
            
            {/* Botón Reagendar para solicitudes canceladas */}
            {solicitud.estado === 'cancelada' && (
                <div className="pt-3 border-t border-gray-200">
                    <PrimaryButton
                        onClick={onAcceptClick}
                        disabled={processing}
                        className="w-full justify-center bg-orange-600 hover:bg-orange-700"
                    >
                        🔄 Reagendar Mentoría
                    </PrimaryButton>
                    <p className="mt-2 text-xs text-center text-gray-500">
                        Esta mentoría fue cancelada. Puedes programar una nueva fecha.
                    </p>
                </div>
            )}
        </div>
    );
}
