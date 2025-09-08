import { useForm, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import PrimaryButton from '@/Components/PrimaryButton';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    const headerMsg = (
        <h2 className="text-lg font-semibold text-gray-900 mb-2 text-center">
            Verifica tu correo electrónico
        </h2>
    );

    const footerElements = (
        <div className="flex flex-col items-center gap-4">
            <PrimaryButton
                disabled={processing}
                onClick={submit}
                type="button"
            >
                Reenviar correo de verificación
            </PrimaryButton>
            <Link
                href={route('logout')}
                method="post"
                as="button"
                className="text-sm text-gray-600 underline hover:text-gray-900"
            >
                Cerrar sesión
            </Link>
        </div>
    );

    return (
        <GuestLayout headerMsg={headerMsg} footerElements={footerElements}>
            <div className="text-sm text-gray-700 text-center">
                {status === 'verification-link-sent'
                    ? 'Se ha enviado un nuevo enlace de verificación a tu correo electrónico.'
                    : 'Por favor, verifica tu correo electrónico antes de continuar.'}
            </div>
        </GuestLayout>
    );
}
