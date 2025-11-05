import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import MisSolicitudesPanel from '@/Components/MisSolicitudesPanel';

export default function MisSolicitudesPage({ misSolicitudes = [] }) {
    // Contar solicitudes por estado
    const pendientes = misSolicitudes.filter(s => s.estado === 'pendiente').length;
    const aceptadas = misSolicitudes.filter(s => s.estado === 'aceptada').length;
    const rechazadas = misSolicitudes.filter(s => s.estado === 'rechazada').length;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Mis Solicitudes de Mentoría
                    </h2>
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            ⏳ {pendientes}
                        </span>
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ✅ {aceptadas}
                        </span>
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            ❌ {rechazadas}
                        </span>
                    </div>
                </div>
            }
        >
            <Head title="Mis Solicitudes" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <MisSolicitudesPanel 
                        solicitudes={misSolicitudes}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
