import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';

// Componente individual de notificación
function NotificationItem({ notification, onMarkAsRead }) {
    const [isMarking, setIsMarking] = useState(false);

    const handleMarkAsRead = () => {
        setIsMarking(true);
        router.post(
            route('student.notifications.read', notification.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (onMarkAsRead) onMarkAsRead(notification.id);
                },
                onFinish: () => setIsMarking(false),
            }
        );
    };

    // Configuración por tipo de notificación
    const typeConfig = {
        SolicitudMentoriaAceptada: {
            icon: '✅',
            bgColor: 'bg-green-50',
            borderColor: 'border-green-200',
            iconBg: 'bg-green-100',
            iconColor: 'text-green-600',
            title: 'Solicitud Aceptada',
            message: (data) => `${data.mentor_nombre} ha aceptado tu solicitud de mentoría`,
        },
        SolicitudMentoriaRechazada: {
            icon: '❌',
            bgColor: 'bg-red-50',
            borderColor: 'border-red-200',
            iconBg: 'bg-red-100',
            iconColor: 'text-red-600',
            title: 'Solicitud Rechazada',
            message: (data) => `${data.mentor_nombre} no pudo aceptar tu solicitud en este momento`,
        },
    };

    const config = typeConfig[notification.type] || {
        icon: '📩',
        bgColor: 'bg-gray-50',
        borderColor: 'border-gray-200',
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        title: 'Notificación',
        message: (data) => 'Tienes una nueva notificación',
    };

    return (
        <div className={`p-4 rounded-lg border ${config.borderColor} ${config.bgColor} hover:shadow-sm transition-shadow`}>
            <div className="flex items-start gap-3">
                {/* Icono */}
                <div className={`flex-shrink-0 w-10 h-10 rounded-full ${config.iconBg} flex items-center justify-center`}>
                    <span className="text-xl">{config.icon}</span>
                </div>

                {/* Contenido */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                        <div className="flex-1">
                            <p className="text-sm font-semibold text-gray-900 mb-1">
                                {config.title}
                            </p>
                            <p className="text-sm text-gray-700 mb-2">
                                {config.message(notification.data)}
                            </p>
                            {notification.data.mentor_experiencia && (
                                <p className="text-xs text-gray-500">
                                    {notification.data.mentor_experiencia} años de experiencia
                                </p>
                            )}
                        </div>
                        
                        {/* Botón marcar como leída */}
                        {!notification.read_at && (
                            <button
                                onClick={handleMarkAsRead}
                                disabled={isMarking}
                                className="flex-shrink-0 text-xs text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50"
                                title="Marcar como leída"
                            >
                                {isMarking ? (
                                    <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                ) : (
                                    '✓ Marcar leída'
                                )}
                            </button>
                        )}
                    </div>

                    {/* Timestamp */}
                    <div className="flex items-center gap-1 mt-2 text-xs text-gray-500">
                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            {formatDistanceToNow(new Date(notification.created_at), { 
                                addSuffix: true, 
                                locale: es 
                            })}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function NotificationsList({ notificaciones = [], contadorNoLeidas = 0 }) {
    const [isMarkingAll, setIsMarkingAll] = useState(false);

    const handleMarkAllAsRead = () => {
        if (contadorNoLeidas === 0) return;
        
        setIsMarkingAll(true);
        router.post(
            route('student.notifications.read-all'),
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsMarkingAll(false),
            }
        );
    };

    if (notificaciones.length === 0) {
        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                        Notificaciones
                    </h3>
                    <div className="text-center py-12 bg-gray-50 rounded-lg">
                        <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h4 className="text-lg font-semibold text-gray-900 mb-2">
                            No tienes notificaciones
                        </h4>
                        <p className="text-gray-600">
                            Aquí aparecerán las respuestas a tus solicitudes de mentoría.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-3">
                        <h3 className="text-lg font-semibold text-gray-900">
                            Notificaciones
                        </h3>
                        {contadorNoLeidas > 0 && (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {contadorNoLeidas} {contadorNoLeidas === 1 ? 'nueva' : 'nuevas'}
                            </span>
                        )}
                    </div>
                    
                    {contadorNoLeidas > 0 && (
                        <button
                            onClick={handleMarkAllAsRead}
                            disabled={isMarkingAll}
                            className="text-sm text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50 flex items-center gap-1"
                        >
                            {isMarkingAll ? (
                                <>
                                    <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Marcando...
                                </>
                            ) : (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Marcar todas como leídas
                                </>
                            )}
                        </button>
                    )}
                </div>

                <div className="space-y-3">
                    {notificaciones.map((notification) => (
                        <NotificationItem
                            key={notification.id}
                            notification={notification}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}
