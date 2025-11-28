import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const StatusPill = ({ active }) => (
    <span
        className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
            active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`}
    >
        {active ? 'Activo' : 'Inactivo'}
    </span>
);

const InfoRow = ({ label, value }) => (
    <div className="space-y-1">
        <p className="text-xs uppercase tracking-wide text-gray-500">{label}</p>
        <p className="text-sm text-gray-900">{value ?? '—'}</p>
    </div>
);

export default function Show({ user }) {
    const mentorAreas = user?.mentor?.areas_interes ?? [];
    const studentAreas = user?.aprendiz?.areas_interes ?? [];

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Detalle de usuario
                </h2>
            }
        >
            <Head title={`Usuario ${user.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 space-y-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <InfoRow label="Nombre" value={user.name} />
                                <InfoRow label="Email" value={user.email} />
                                <InfoRow label="Rol" value={user.role} />
                                <div className="space-y-1">
                                    <p className="text-xs uppercase tracking-wide text-gray-500">Estado</p>
                                    <StatusPill active={user.is_active} />
                                </div>
                                <InfoRow
                                    label="Fecha de registro"
                                    value={user.created_at}
                                />
                            </div>

                            {studentAreas.length > 0 && (
                                <div>
                                    <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                                        Áreas de interés (estudiante)
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {studentAreas.map((area) => (
                                            <span
                                                key={area.id ?? area.nombre}
                                                className="px-2 py-1 text-xs rounded bg-blue-50 text-blue-700 border border-blue-100"
                                            >
                                                {area.nombre}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {mentorAreas.length > 0 && (
                                <div>
                                    <p className="text-xs uppercase tracking-wide text-gray-500 mb-2">
                                        Áreas de especialidad (mentor)
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {mentorAreas.map((area) => (
                                            <span
                                                key={area.id ?? area.nombre}
                                                className="px-2 py-1 text-xs rounded bg-indigo-50 text-indigo-700 border border-indigo-100"
                                            >
                                                {area.nombre}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="flex items-center gap-3">
                                <Link
                                    href={route('admin.users.edit', user.id)}
                                    className="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Editar
                                </Link>
                                <Link
                                    href={route('admin.users')}
                                    className="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Volver
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
