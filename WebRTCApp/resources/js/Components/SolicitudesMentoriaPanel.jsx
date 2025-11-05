import React, { useState } from 'react';
import { Tab } from '@headlessui/react';
import SolicitudMentoriaCard from '@/Components/SolicitudMentoriaCard';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function SolicitudesMentoriaPanel({ solicitudes = [], mentorProfile }) {
    const [selectedTab, setSelectedTab] = useState(0);
    
    // Verificar si el perfil está bloqueado
    const isBlocked = !mentorProfile?.cv_verified;

    // Filtrar solicitudes por estado
    const pendientes = solicitudes.filter(s => s.estado === 'pendiente');
    const aceptadas = solicitudes.filter(s => s.estado === 'aceptada');
    const rechazadas = solicitudes.filter(s => s.estado === 'rechazada');

    const categories = [
        { name: 'Pendientes', count: pendientes.length, data: pendientes },
        { name: 'Aceptadas', count: aceptadas.length, data: aceptadas },
        { name: 'Rechazadas', count: rechazadas.length, data: rechazadas },
    ];

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                    Solicitudes de Mentoría
                </h3>

                {/* Estado bloqueado */}
                {isBlocked ? (
                    <div className="p-8 text-center bg-gray-50 rounded-lg border-2 border-gray-200">
                        <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h4 className="text-lg font-semibold text-gray-900 mb-2">
                            Panel Bloqueado
                        </h4>
                        <p className="text-gray-600 mb-4 max-w-md mx-auto">
                            Debes tener tu CV verificado y tu perfil completo para recibir y gestionar solicitudes de mentoría.
                        </p>
                        <a 
                            href="/profile"
                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Completar Perfil
                        </a>
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
                                            <svg className="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p>No hay solicitudes {category.name.toLowerCase()}</p>
                                        </div>
                                    ) : (
                                        <ul className="space-y-4">
                                            {category.data.map((solicitud) => (
                                                <SolicitudMentoriaCard
                                                    key={solicitud.id}
                                                    solicitud={solicitud}
                                                    showActions={category.name === 'Pendientes'}
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
