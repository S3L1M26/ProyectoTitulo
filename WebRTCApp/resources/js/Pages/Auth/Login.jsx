import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const headerMsg = (
        <h3 className="text-gray-900 text-xl font-medium mb-6 text-center">
            Inicia sesión en <strong className="text-green-600">Connect</strong>        
        </h3>
    );
    
    const footerElements = (
        <div className="space-y-4 text-center">
            <PrimaryButton
                type="submit"
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-normal text-xs py-2 px-4 rounded-md transition duration-150 ease-in-out flex justify-center items-center"
                disabled={processing}
            >
                INICIAR SESIÓN
            </PrimaryButton>
            <div className="text-center">
                <p className="text-sm text-gray-600">
                    ¿Olvidaste tu contraseña?{' '}
                    <Link className="text-green-600 hover:underline" href={route('password.request')}>
                        Recupérala aquí
                    </Link>
                </p>
            </div>
        </div>
    );
    
    return (
        <GuestLayout onSubmit={submit} headerMsg={headerMsg} footerElements={footerElements}>
            <Head title="Log in" />
    
            {/* Status Message */}
            {status && (
                <div className="bg-green-100 border border-green-200 text-green-700 text-sm font-medium p-4 rounded-md mb-4 text-center">
                    {status}
                </div>
            )}
    
            {/* Email Field */}
            <div className="mb-4">
                <InputLabel htmlFor="email" value="Email" className="block text-sm font-medium text-gray-700 mb-1" />
                <div className="flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                    <span className="material-icons text-gray-500 mr-2">perm_identity</span>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="w-full focus:outline-none"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                </div>
                <InputError message={errors.email} className="mt-1 text-red-600 text-sm" />
            </div>
    
            {/* Password Field */}
            <div className="mb-4">
                <InputLabel htmlFor="password" value="Contraseña" className="block text-sm font-medium text-gray-700 mb-1" />
                <div className="flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                    <span className="material-icons text-gray-500 mr-2">lock_outline</span>
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="w-full focus:outline-none"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />
                </div>
                <InputError message={errors.password} className="mt-1 text-red-600 text-sm" />
            </div>
    
            {/* Remember Me Checkbox */}
            <div className="flex items-center mb-4">
                <Checkbox
                    name="remember"
                    checked={data.remember}
                    onChange={(e) => setData('remember', e.target.checked)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                    Recordar sesión
                </label>
            </div>
        </GuestLayout>
    );    
}
