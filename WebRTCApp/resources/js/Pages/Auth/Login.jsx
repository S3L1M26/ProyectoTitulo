import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export default function Login({ status, canResetPassword, role, allowAdmin = false }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
        role: role ?? 'student', 
    });

    const isAdmin = data.role === 'admin';

    const changeRole = (newRole) => {
        if (newRole === 'admin' && !allowAdmin) {
            return;
        }
        setData('role', newRole);
    };

    const submit = (e) => {
        e.preventDefault();

        const routeName = isAdmin ? 'admin.login.store' : 'login.store';

        post(route(routeName), {
            onFinish: () => reset('password'),
        });
    };

    // const switchRole = (newRole) => {
    //     window.location.href = route('login.show', newRole);
    // };

    const headerMsg = (
        <div className="space-y-3">
            <h3 className="text-gray-900 text-xl font-medium text-center">
                {isAdmin ? (
                    <p>Área de <strong className="text-[#ec3636]">Administradores</strong></p>
                ) : data.role === 'student' ? (
                    <p>Área de <strong className="text-[#ec3636]">Estudiantes</strong></p>
                ) : (
                    <p>Área de <strong className="text-[#ec3636]">Mentores</strong></p>
                )}        
            </h3>
            <div className="flex items-center justify-center gap-2 text-xs">
                <button
                    type="button"
                    onClick={() => changeRole('student')}
                    className={`px-3 py-1 rounded-full border ${data.role === 'student' ? 'bg-red-100 border-red-400 text-red-700' : 'border-gray-300 text-gray-600 hover:border-gray-400'}`}
                >
                    Estudiante
                </button>
                <button
                    type="button"
                    onClick={() => changeRole('mentor')}
                    className={`px-3 py-1 rounded-full border ${data.role === 'mentor' ? 'bg-red-100 border-red-400 text-red-700' : 'border-gray-300 text-gray-600 hover:border-gray-400'}`}
                >
                    Mentor
                </button>
                {allowAdmin && (
                    <button
                        type="button"
                        onClick={() => changeRole('admin')}
                        className={`px-3 py-1 rounded-full border ${isAdmin ? 'bg-red-100 border-red-400 text-red-700' : 'border-gray-300 text-gray-600 hover:border-gray-400'}`}
                    >
                        Admin
                    </button>
                )}
            </div>
        </div>
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
            {!isAdmin && (
                <div className="text-center space-y-2">
                    <p className="text-sm text-gray-600">
                        ¿No tienes cuenta?{' '}
                        <Link 
                            className="text-[#f00808] hover:underline" 
                            href={route('register', {role: data.role})}
                        >
                            Regístrate aquí
                        </Link>
                    </p>
                    {canResetPassword && (
                        <p className="text-sm text-gray-600">
                            ¿Olvidaste tu contraseña?{' '}
                            <Link className="text-[#f00808] hover:underline" href={route('password.request')}>
                                Recupérala aquí
                            </Link>
                        </p>
                    )}
                </div>
            )}
        </div>
    );
    
    return (
        <GuestLayout onSubmit={submit} headerMsg={headerMsg} footerElements={footerElements}>
            <Head title={`Inicio Sesión - ${isAdmin ? 'Administrador' : data.role === 'student' ? 'Estudiante' : 'Mentor'}`} />
    
            {/* Status Message */}
            {status && (
                <div className="bg-green-100 border border-green-200 text-red-700 text-sm font-medium p-4 rounded-md mb-4 text-center">
                    {status}
                </div>
            )}
    
            {/* Email Field */}
            <div className="mb-4">
                <InputLabel htmlFor="email" value="Correo electrónico" className="block text-sm font-medium text-gray-700 mb-1" />
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
