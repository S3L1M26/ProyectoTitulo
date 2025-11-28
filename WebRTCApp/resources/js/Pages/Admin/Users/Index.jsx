import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import React from 'react';

const StatusPill = ({ active }) => (
    <span
        className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
            active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`}
    >
        {active ? 'Activo' : 'Inactivo'}
    </span>
);

const Pagination = ({ links = [] }) => {
    if (!Array.isArray(links) || links.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2 mt-4">
            {links.map((link, idx) =>
                link.url ? (
                    <Link
                        key={`${link.url}-${idx}`}
                        href={link.url}
                        preserveScroll
                        preserveState
                        className={`px-3 py-1 rounded border text-sm ${
                            link.active
                                ? 'bg-gray-800 text-white border-gray-800'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={`disabled-${idx}-${link.label}`}
                        className="px-3 py-1 rounded border text-sm text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </div>
    );
};

export default function Index({ users, stats }) {
    const rows = users?.data ?? [];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Usuarios
                    </h2>
                </div>
            }
        >
            <Head title="Usuarios" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    {stats && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div className="bg-white shadow-sm sm:rounded-lg p-4">
                                <p className="text-sm text-gray-500">Total usuarios</p>
                                <p className="text-2xl font-semibold text-gray-800">{stats.total_users}</p>
                            </div>
                            <div className="bg-white shadow-sm sm:rounded-lg p-4">
                                <p className="text-sm text-gray-500">Estudiantes</p>
                                <p className="text-2xl font-semibold text-gray-800">{stats.total_students}</p>
                            </div>
                            <div className="bg-white shadow-sm sm:rounded-lg p-4">
                                <p className="text-sm text-gray-500">Mentores</p>
                                <p className="text-2xl font-semibold text-gray-800">{stats.total_mentors}</p>
                            </div>
                            <div className="bg-white shadow-sm sm:rounded-lg p-4">
                                <p className="text-sm text-gray-500">Estudiantes verificados</p>
                                <p className="text-2xl font-semibold text-gray-800">{stats.verified_students}</p>
                            </div>
                            <div className="bg-white shadow-sm sm:rounded-lg p-4">
                                <p className="text-sm text-gray-500">Mentores verificados</p>
                                <p className="text-2xl font-semibold text-gray-800">{stats.verified_mentors}</p>
                            </div>
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Nombre
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Rol
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Estado
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {rows.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan="5"
                                                    className="px-4 py-6 text-center text-sm text-gray-500"
                                                >
                                                    No hay usuarios para mostrar.
                                                </td>
                                            </tr>
                                        ) : (
                                            rows.map((user) => (
                                                <tr key={user.id}>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                        {user.name}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                        {user.email}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                        {user.role}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                        <StatusPill active={user.is_active} />
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-right space-x-3">
                                                        <Link
                                                            href={route('admin.users.show', user.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 font-medium"
                                                        >
                                                            Ver
                                                        </Link>
                                                        <Link
                                                            href={route('admin.users.edit', user.id)}
                                                            className="text-gray-700 hover:text-gray-900 font-medium"
                                                        >
                                                            Editar
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination links={users?.links} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
