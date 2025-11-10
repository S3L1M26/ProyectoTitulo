import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProfileReminderNotification from '@/Components/ProfileReminderNotification';
import MentoriaCard from '@/Components/MentoriaCard';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Dashboard({ mentorProfile, mentoriasProgramadas = [] }) {
    const { flash } = usePage().props;
    const [filtro, setFiltro] = useState('todas');
    
    const mentoriasFiltradas = useMemo(() => {
        const hoy = new Date();
        const startOfDay = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
        const endOfDay = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1);
        const startOfWeek = new Date(hoy);
        startOfWeek.setDate(hoy.getDate() - hoy.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 7);

        const list = Array.isArray(mentoriasProgramadas) ? mentoriasProgramadas : [];
        if (filtro === 'hoy') {
            return list.filter(m => {
                const f = new Date(m.fecha);
                return f >= startOfDay && f < endOfDay;
            });
        }
        if (filtro === 'semana') {
            return list.filter(m => {
                const f = new Date(m.fecha);
                return f >= startOfWeek && f < endOfWeek;
            });
        }
        return list;
    }, [mentoriasProgramadas, filtro]);

    // Mostrar mensaje de éxito si existe
    useEffect(() => {
        if (flash?.success) {
            const timer = setTimeout(() => {
                alert(flash.success);
            }, 100);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    return (
        <AuthenticatedLayout
            header={
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Panel de Mentor
                    </h2>
            }
        >
            <Head title="Panel de Mentor" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    {/* Notificación de perfil incompleto */}
                    <ProfileReminderNotification />
                    
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h4 className="text-lg font-semibold text-gray-800 mb-4">¡Bienvenido a tu panel de mentor!</h4>
                            <p className="text-gray-600">Aquí podrás gestionar tus sesiones de mentoría y ayudar a estudiantes en su orientación profesional.</p>
                        </div>
                    </div>

                    {/* Mentorías Programadas */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">Mentorías Programadas</h3>
                                <div className="flex gap-2">
                                    <button className={`px-3 py-1 rounded ${filtro === 'todas' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`} onClick={() => setFiltro('todas')}>Todas</button>
                                    <button className={`px-3 py-1 rounded ${filtro === 'hoy' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`} onClick={() => setFiltro('hoy')}>Hoy</button>
                                    <button className={`px-3 py-1 rounded ${filtro === 'semana' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'}`} onClick={() => setFiltro('semana')}>Esta semana</button>
                                </div>
                            </div>
                            {mentoriasFiltradas.length === 0 ? (
                                <div className="text-center text-gray-500 py-8">No hay mentorías programadas.</div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {mentoriasFiltradas.map(m => (
                                        <MentoriaCard key={m.id} mentoria={m} userRole="mentor" />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
