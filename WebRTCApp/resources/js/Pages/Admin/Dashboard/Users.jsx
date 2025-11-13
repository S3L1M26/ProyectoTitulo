import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import { useState } from 'react';

function Row({ row }) {
    const [open, setOpen] = useState(false);

    return(
        <React.Fragment>
            <tr className="border-b">
                <td className="px-4 py-2">
                    <button onClick={() => setOpen(!open)}>
                        {open ? '-' : '+'}
                    </button>
                </td>
                <td className="px-4 py-2">{row.name}</td>
                <td className="px-4 py-2">{row.email}</td>
                <td className="px-4 py-2">{row.role}</td>
            </tr>
            {open && (
                <tr>
                    <td colSpan="4" className="px-4 py-2">
                        
                    </td>
                </tr>
            )}
        </React.Fragment>
    );
}

function editUser(id) {
    router.visit(route('admin.users.edit', id));
}

Row.propTypes = {
    row: PropTypes.shape({
        name: PropTypes.string.isRequired,
        email: PropTypes.string.isRequired,
        role: PropTypes.string.isRequired,
    }).isRequired,
};

export default function Users({ users }) {
    const rows = users;

    return (
        <AuthenticatedLayout 
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Ver Usuarios
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-2"></th>
                                        <th className="px-4 py-2">Name</th>
                                        <th className="px-4 py-2">Email</th>
                                        <th className="px-4 py-2">Role</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {rows.map((row) => (
                                        <Row key={row.id} row={row} />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );

}