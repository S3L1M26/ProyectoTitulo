import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    const headerMsg = (
        <h3 className="text-gray-900 text-xl font-medium mb-6 text-center">
            Recupera tu <strong className="text-green-600">contraseña</strong>
        </h3>
    );

    const footer = (
        <div className="space-y-4 text-center">
            <PrimaryButton 
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-normal text-xs py-2 px-4 rounded-md transition duration-150 ease-in-out flex justify-center items-center" 
                disabled={processing}
            >
                RESTABLECER CONTRASEÑA
            </PrimaryButton>
        </div>
    );

    return (
        <GuestLayout onSubmit={submit} headerMsg={headerMsg} footerElements={footer}>
            <Head title="Forgot Password" />

            <div className="mb-4 text-sm text-gray-600">
                ¿Olvidaste tu contraseña? No hay problema. Solo déjanos tu dirección
                de correo electrónico y te enviaremos un enlace para restablecer tu
                contraseña que te permitirá elegir una nueva.
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="mb-4">
                <div className="flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                    <span className="material-icons text-gray-500 mr-2">email</span>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="w-full focus:outline-none"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                </div>
                <InputError message={errors.email} className="mt-1 text-red-600 text-sm" />
            </div>
        </GuestLayout>
    );
}
