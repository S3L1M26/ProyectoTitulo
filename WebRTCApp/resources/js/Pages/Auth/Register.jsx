import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Register({ role }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: role,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
            onError: () => {}
        });
    };

    const headerMsg = (
        <h3 className="text-gray-900 text-xl font-medium mb-4 text-center">
            Registro {role === 'mentor' ? (
                    <strong className="text-[#ec3636]">Mentor</strong>
                ) : (
                    <strong className="text-[#ec3636]">Estudiante</strong>
                )}
        </h3>
    );

    const footerElements = (
        <div className="space-y-4 text-center">
            <PrimaryButton 
                type="submit"
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-normal text-xs py-2 px-4 rounded-md transition duration-150 ease-in-out flex justify-center items-center" 
                disabled={processing}
            >
                REGISTRAR
            </PrimaryButton>
            <div className="text-center">
                <p className="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?{' '}
                    <Link
                        className="text-[#f00808] hover:underline"
                        href={route('login', { role })}
                    >
                        Inicia sesión aquí
                    </Link>
                </p>
            </div>
        </div>
    );

    return (
        <GuestLayout headerMsg={headerMsg} footerElements={footerElements} onSubmit={submit}>
            <Head title={`Registro - ${role === 'mentor' ? 'Mentor' : 'Estudiante'}`} />

            <div className="space-y-3 max-w-md mx-auto">
                {/* Nombre */}
                <div>
                    <InputLabel htmlFor="name" value="Nombre" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">perm_identity</span>
                        <TextInput
                            id="name"
                            name="name"
                            value={data.name}
                            className="w-full focus:outline-none"
                            autoComplete="name"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.name} className="mt-1 text-red-600 text-sm" />
                </div>
                {/* Email */}
                <div>
                    <InputLabel htmlFor="email" value="Correo electrónico" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
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
                {/* Contraseña */}
                <div>
                    <InputLabel htmlFor="password" value="Contraseña" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">lock_outline</span>
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="w-full focus:outline-none"
                            autoComplete="new-password"
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.password} className="mt-1 text-red-600 text-sm" />
                </div>
                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirmar Contraseña" />
                    <div className="flex items-center border border-gray-300 rounded-md px-3 py-2">
                        <span className="material-icons text-gray-500 mr-2">verified_user</span>
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="w-full focus:outline-none"
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.password_confirmation} className="mt-1 text-red-600 text-sm" />
                </div>
            </div>
        </GuestLayout>
    );
}
