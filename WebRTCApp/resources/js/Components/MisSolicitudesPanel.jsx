import React, { useState } from 'react';
import { Tab } from '@headlessui/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

// Componente individual de solicitud del estudiante
function SolicitudCard({ solicitud }) {
    const estadoConfig = {
        pendiente: {
            badge: 'bg-yellow-100 text-yellow-800',
            icon: '⏳',
            label: 'Pendiente'
        },
        aceptada: {
            badge: 'bg-green-100 text-green-800',
            icon: '✅',
            label: 'Aceptada'
        },
        rechazada: {
            badge: 'bg-red-100 text-red-800',
            icon: '❌',
            label: 'Rechazada'
        }
    };

    const config = estadoConfig[solicitud.estado] || estadoConfig.pendiente;

    return (
        <li className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white">
            <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <h4 className="text-lg font-semibold text-gray-900">
                            {solicitud.mentor.name}
                        </h4>
                        <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${config.badge}`}>
                            <span>{config.icon}</span>
                            {config.label}
                        </span>
                    </div>
                    <p className="text-sm text-gray-600">
                        {solicitud.mentor.años_experiencia} años de experiencia
                    </p>
                </div>
            </div>

            {/* Biografía del mentor */}
            {solicitud.mentor.biografia && (
                <div className="mb-3">
                    <p className="text-sm text-gray-700 line-clamp-2">
                        {solicitud.mentor.biografia}
                    </p>
                </div>
            )}

            {/* Áreas de interés del mentor */}
            {solicitud.mentor.areas_interes && solicitud.mentor.areas_interes.length > 0 && (
                <div className="mb-3">
                    <p className="text-xs text-gray-500 mb-1">Áreas de interés:</p>
                    <div className="flex flex-wrap gap-1">
                        {solicitud.mentor.areas_interes.slice(0, 3).map((area) => (
                            <span 
                                key={area.id} 
                                className="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded"
                            >
                                {area.nombre}
                            </span>
                        ))}
                        {solicitud.mentor.areas_interes.length > 3 && (
                            <span className="text-xs text-gray-500 self-center">
                                +{solicitud.mentor.areas_interes.length - 3} más
                            </span>
                        )}
                    </div>
                </div>
            )}

            {/* Mensaje enviado */}
            {solicitud.mensaje && (
                <div className="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <p className="text-xs text-gray-500 mb-1">Tu mensaje:</p>
                    <p className="text-sm text-gray-700 italic">"{solicitud.mensaje}"</p>
                </div>
            )}

            {/* Fechas */}
            <div className="flex flex-wrap gap-4 text-xs text-gray-500 pt-3 border-t border-gray-100">
                <div className="flex items-center gap-1">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        Enviada: {solicitud.fecha_solicitud 
                            ? format(new Date(solicitud.fecha_solicitud), "d 'de' MMMM, yyyy", { locale: es })
                            : format(new Date(solicitud.created_at), "d 'de' MMMM, yyyy", { locale: es })
                        }
                    </span>
                </div>
                {solicitud.fecha_respuesta && (
                    <div className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            Respondida: {format(new Date(solicitud.fecha_respuesta), "d 'de' MMMM, yyyy", { locale: es })}
                        </span>
                    </div>
                )}
            </div>
        </li>
    );
}

export default function MisSolicitudesPanel({ solicitudes = [] }) {
    const [selectedTab, setSelectedTab] = useState(0);

    // Filtrar solicitudes por estado
    const pendientes = solicitudes.filter(s => s.estado === 'pendiente');
    const aceptadas = solicitudes.filter(s => s.estado === 'aceptada');
    const rechazadas = solicitudes.filter(s => s.estado === 'rechazada');

    const categories = [
        { 
            name: 'Pendientes', 
            count: pendientes.length, 
            data: pendientes,
            emptyIcon: '⏳',
            emptyMessage: 'No tienes solicitudes pendientes'
        },
        { 
            name: 'Aceptadas', 
            count: aceptadas.length, 
            data: aceptadas,
            emptyIcon: '✅',
            emptyMessage: 'No tienes solicitudes aceptadas aún'
        },
        { 
            name: 'Rechazadas', 
            count: rechazadas.length, 
            data: rechazadas,
            emptyIcon: '❌',
            emptyMessage: 'No tienes solicitudes rechazadas'
        },
    ];

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900">
                        Mis Solicitudes de Mentoría
                    </h3>
                    <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        Total: {solicitudes.length}
                    </span>
                </div>

                {solicitudes.length === 0 ? (
                    <div className="text-center py-12 bg-gray-50 rounded-lg">
                        <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <h4 className="text-lg font-semibold text-gray-900 mb-2">
                            No has enviado solicitudes aún
                        </h4>
                        <p className="text-gray-600 mb-4">
                            Explora los mentores sugeridos y envía tu primera solicitud de mentoría.
                        </p>
                    </div>
                ) : (
                    <Tab.Group selectedIndex={selectedTab} onChange={setSelectedTab}>
                        <Tab.List className="flex space-x-1 rounded-xl bg-blue-900/20 p-1 mb-6">
                            {categories.map((category) => (
                                <Tab
                                    key={category.name}
                                    className={({ selected }) =>
                                        classNames(
                                            'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                            'ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2',
                                            selected
                                                ? 'bg-white shadow text-blue-700'
                                                : 'text-blue-700 hover:bg-white/[0.12] hover:text-blue-800'
                                        )
                                    }
                                >
                                    {category.name} ({category.count})
                                </Tab>
                            ))}
                        </Tab.List>
                        <Tab.Panels>
                            {categories.map((category, idx) => (
                                <Tab.Panel
                                    key={idx}
                                    className={classNames(
                                        'rounded-xl bg-white',
                                        'ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2'
                                    )}
                                >
                                    {category.data.length === 0 ? (
                                        <div className="text-center py-12 text-gray-500">
                                            <div className="text-4xl mb-3">{category.emptyIcon}</div>
                                            <p className="text-gray-600">{category.emptyMessage}</p>
                                        </div>
                                    ) : (
                                        <ul className="space-y-4">
                                            {category.data.map((solicitud) => (
                                                <SolicitudCard
                                                    key={solicitud.id}
                                                    solicitud={solicitud}
                                                />
                                            ))}
                                        </ul>
                                    )}
                                </Tab.Panel>
                            ))}
                        </Tab.Panels>
                    </Tab.Group>
                )}
            </div>
        </div>
    );
}
