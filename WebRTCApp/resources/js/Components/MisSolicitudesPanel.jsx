import React, { useState, useEffect, useRef } from 'react';
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

    const [expanded, setExpanded] = useState(false);
    return (
        <li className="border border-gray-200 rounded-lg bg-white shadow-sm hover:shadow-md transition-shadow">
            <button
                type="button"
                onClick={() => setExpanded(e => !e)}
                className="w-full text-left p-4 flex items-start justify-between"
            >
                <div className="flex-1 pr-4">
                    <div className="flex items-center gap-2 mb-2">
                        <h4 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            {solicitud.mentor.name}
                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${config.badge}`}>
                                <span>{config.icon}</span>
                                {config.label}
                            </span>
                        </h4>
                    </div>
                    <p className="text-sm text-gray-600">
                        {solicitud.mentor.años_experiencia} años de experiencia
                    </p>
                    {solicitud.mensaje && (
                        <p className="mt-2 text-xs text-gray-500 line-clamp-1 italic">"{solicitud.mensaje}"</p>
                    )}
                </div>
                <div className="flex flex-col items-end gap-2">
                    <span className="text-xs text-gray-400">
                        {solicitud.fecha_solicitud ? format(new Date(solicitud.fecha_solicitud), 'd/MM/yyyy', { locale: es }) : ''}
                    </span>
                    <svg className={`w-5 h-5 text-gray-500 transition-transform ${expanded ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>
            {expanded && (
                <div className="px-4 pb-4 pt-2 border-t border-gray-100 space-y-3">
                    {solicitud.mentor.biografia && (
                        <p className="text-sm text-gray-700">{solicitud.mentor.biografia}</p>
                    )}
                    {solicitud.mentor.areas_interes?.length > 0 && (
                        <div>
                            <p className="text-xs text-gray-500 mb-1">Áreas de interés:</p>
                            <div className="flex flex-wrap gap-1">
                                {solicitud.mentor.areas_interes.slice(0,5).map(area => (
                                    <span key={area.id} className="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded">{area.nombre}</span>
                                ))}
                            </div>
                        </div>
                    )}
                    {solicitud.mensaje && (
                        <div className="p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <p className="text-xs text-gray-500 mb-1">Tu mensaje completo:</p>
                            <p className="text-sm text-gray-700 italic">"{solicitud.mensaje}"</p>
                        </div>
                    )}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-gray-600">
                        <div className="flex items-center gap-1">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Enviada: {solicitud.fecha_solicitud ? format(new Date(solicitud.fecha_solicitud), "d 'de' MMMM, yyyy", { locale: es }) : format(new Date(solicitud.created_at), "d 'de' MMMM, yyyy", { locale: es })}</span>
                        </div>
                        {solicitud.fecha_respuesta && (
                            <div className="flex items-center gap-1">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Respondida: {format(new Date(solicitud.fecha_respuesta), "d 'de' MMMM, yyyy", { locale: es })}</span>
                            </div>
                        )}
                    </div>
                    {solicitud.mentoria && solicitud.mentoria.enlace_reunion && (
                        <div className="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p className="text-xs font-semibold text-green-700 mb-1">Mentoría confirmada</p>
                            <p className="text-xs text-green-700">Fecha: {solicitud.mentoria.fecha_formateada} · Hora: {solicitud.mentoria.hora_formateada}</p>
                            <a href={solicitud.mentoria.enlace_reunion} target="_blank" rel="noopener noreferrer" className="mt-2 inline-flex items-center text-sm text-green-800 underline">Unirme a la reunión →</a>
                        </div>
                    )}
                </div>
            )}
        </li>
    );
}

export default function MisSolicitudesPanel({ solicitudes = [], pollingConfig = { interval_ms: 10000 } }) {
    const [selectedTab, setSelectedTab] = useState(0);
    const [data, setData] = useState(solicitudes);
    const [etag, setEtag] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const intervalRef = useRef(null);

    // Polling effect
    useEffect(() => {
        const poll = async () => {
            setError(null);
            try {
                const url = `/api/student/solicitudes` + (etag ? `?etag=${etag}` : '');
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    throw new Error('Error al obtener solicitudes');
                }
                const json = await res.json();
                if (json.changed) {
                    setData(json.items || []);
                }
                if (json.etag) setEtag(json.etag);
            } catch (e) {
                setError(e.message);
            }
        };

        // Primera ejecución inmediata
        poll();
        intervalRef.current = setInterval(poll, pollingConfig.interval_ms || 10000);
        return () => clearInterval(intervalRef.current);
    }, []);

    // Actualizar datos si prop inicial cambia (navegación Inertia)
    useEffect(() => {
        setData(solicitudes);
    }, [solicitudes]);

    // Filtrar solicitudes por estado
    const pendientes = data.filter(s => s.estado === 'pendiente');
    const aceptadas = data.filter(s => s.estado === 'aceptada');
    const rechazadas = data.filter(s => s.estado === 'rechazada');

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
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            Total: {data.length}
                        </span>
                        {error && (
                            <span className="text-xs text-red-600">Error: {error}</span>
                        )}
                    </div>
                </div>
                {data.length === 0 ? (
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
