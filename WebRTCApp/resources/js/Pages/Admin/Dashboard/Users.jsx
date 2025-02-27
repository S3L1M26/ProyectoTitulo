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
                        <div className="bg-gray-100 p-4 rounded">
                            <h4 className="font-semibold">Cuenta SIP</h4>
                            <ul>
                                {row.sipAccounts.map((sip_account) => (
                                    <li key={sip_account.id}>
                                        ID Sip: {sip_account.sip_user_id} - ID Usuario: {sip_account.user_id}
                                    </li>
                                ))}
                            </ul>
                            <h4 className="font-semibold mt-4">AORs</h4>
                            <ul>
                                {row.ps_aors.map((aor) => (
                                    <li key={aor.id}>
                                        Max Contacts: {aor.max_contacts}, Qualify Frequency: {aor.qualify_frequency}
                                    </li>
                                ))}
                            </ul>
                            <h4 className="font-semibold mt-4">Endpoints</h4>
                            <ul>
                                {row.ps_endpoints.map((endpoint) => (
                                    <li key={endpoint.id}>
                                        Allow: {endpoint.allow}, Direct Media: {endpoint.direct_media ? 'Yes' : 'No'}, Mailboxes: {endpoint.mailboxes}
                                    </li>
                                ))}
                            </ul>
                            <button onClick={() => editUser(row.id) } className="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Editar
                            </button>
                        </div>
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
        sipAccounts: PropTypes.arrayOf(
            PropTypes.shape({
                id: PropTypes.number.isRequired,
                sip_user_id: PropTypes.string.isRequired,
                user_id: PropTypes.number.isRequired,
            })
        ).isRequired,
        ps_aors: PropTypes.arrayOf(
            PropTypes.shape({
                id: PropTypes.string.isRequired,
                max_contacts: PropTypes.number.isRequired,
                qualify_frequency: PropTypes.number.isRequired,
            })
        ).isRequired,
        ps_endpoints: PropTypes.arrayOf(
            PropTypes.shape({
                id: PropTypes.string.isRequired,
                allow: PropTypes.string.isRequired,
                direct_media: PropTypes.bool.isRequired,
                mailboxes: PropTypes.string.isRequired,
            })
        ).isRequired,
    }).isRequired,
};

export default function Users({ users, sipUsers, ps_aors, ps_endpoints}) {
    const rows = users.map((user) => {
        const userSipAccounts = sipUsers.filter((sipUser) => sipUser.user_id === user.id).map(sipUser => ({
            ...sipUser,
            sip_user_id: String(sipUser.sip_user_id),
        }));

        const userSipUserIds = userSipAccounts.map(sipAccount => sipAccount.sip_user_id);

        const userAors = ps_aors.filter((aor) => userSipUserIds.includes(aor.id));
        const userEndpoints = ps_endpoints.filter((endpoint) => userSipUserIds.includes(endpoint.id)).map(endpoint => ({
            ...endpoint,
            direct_media: endpoint.direct_media === 'yes' ? true : false,
        }));

        return {
            ...user,
            sipAccounts: userSipAccounts,
            ps_aors: userAors,
            ps_endpoints: userEndpoints,
        };
    });

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