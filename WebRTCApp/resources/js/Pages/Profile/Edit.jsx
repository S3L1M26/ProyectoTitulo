import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import UpdateAprendizProfile from './Partials/UpdateAprendizProfile';
import UpdateMentorProfile from './Partials/UpdateMentorProfile';
import StudentCertificate from './Partials/StudentCertificate';
import MentorCV from './Partials/MentorCV';
import ProfileProgress from '@/Components/ProfileProgress';

export default function Edit({ mustVerifyEmail, status, certificate, mentorCv, cvVerified }) {
    const { auth } = usePage().props;
    const user = auth.user;
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Perfil
                </h2>
            }
        >
            <Head title="Perfil" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Estado general del perfil - Badge simple */}
                    {(user.role === 'student' || user.role === 'mentor') && (
                        <ProfileProgress />
                    )}

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    {/* Formulario específico para estudiantes */}
                    {user.role === 'student' && (
                        <>
                            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8" id="certificate">
                                <StudentCertificate certificate={certificate} className="max-w-xl" />
                            </div>
                            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                                <UpdateAprendizProfile className="max-w-xl" />
                            </div>
                        </>
                    )}

                    {/* Formulario específico para mentores */}
                    {user.role === 'mentor' && (
                        <>
                            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8" id="cv">
                                <MentorCV cv={mentorCv} cvVerified={cvVerified} className="max-w-xl" />
                            </div>
                            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                                <UpdateMentorProfile cvVerified={cvVerified} className="max-w-xl" />
                            </div>
                        </>
                    )}

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
