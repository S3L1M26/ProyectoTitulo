import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import ProfileIncompleteIcon from '@/Components/ProfileIncompleteIcon';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
        const { contadorNoLeidas, solicitudesPendientes } = usePage().props;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const redirectTo = user.role === 'mentor' ? 'mentor.dashboard' : user.role === 'student' ? 'student.dashboard' : 'admin.dashboard';

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <img
                                        className='h-10 w-10 fill-current text-gray-500'
                                        src="/images/favicons/faviconGrande.png" // Replace with the correct path to your logo
                                        alt="Application Logo"
                                    />
                                </Link>
                            </div>

                            
                            {user.role === 'admin' ? (
                                <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                    <NavLink
                                        href={route('admin.dashboard')}
                                        active={route().current('admin.dashboard')}
                                    >
                                        Dashboard
                                    </NavLink>
                                    <NavLink
                                        href={route('admin.users')}
                                        active={route().current('admin.users')}
                                    >
                                        Usuarios
                                    </NavLink>
                                </div>
                            ) : user.role === 'student' ? (
                                <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                    <NavLink
                                        href={route('student.dashboard')}
                                        active={route().current('student.dashboard')}
                                    >
                                        Panel de Usuario
                                    </NavLink>
                                    <NavLink
                                        href={route('student.solicitudes')}
                                        active={route().current('student.solicitudes')}
                                    >
                                        Mis Solicitudes
                                    </NavLink>
                                    <NavLink
                                        href={route('student.notifications')}
                                        active={route().current('student.notifications')}
                                    >
                                        <div className="flex items-center gap-2">
                                            <span>Notificaciones</span>
                                            {contadorNoLeidas > 0 && (
                                                <span className="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">
                                                    {contadorNoLeidas > 9 ? '9+' : contadorNoLeidas}
                                                </span>
                                            )}
                                        </div>
                                    </NavLink>
                                </div>
                            ) : (
                                <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                    <NavLink
                                        href={route(redirectTo)}
                                        active={route().current(redirectTo)}
                                    >
                                        Panel de Usuario
                                    </NavLink>
                                        {user.role === 'mentor' && (
                                            <NavLink
                                                href={route('mentor.solicitudes')}
                                                active={route().current('mentor.solicitudes')}
                                            >
                                                <div className="flex items-center gap-2">
                                                    <span>Solicitudes</span>
                                                    {solicitudesPendientes > 0 && (
                                                        <span className="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">
                                                            {solicitudesPendientes > 9 ? '9+' : solicitudesPendientes}
                                                        </span>
                                                    )}
                                                </div>
                                            </NavLink>
                                        )}
                                </div>
                            )}
                            
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                <span className="flex items-center">
                                                    {user.name}
                                                    <ProfileIncompleteIcon className="ml-2" />
                                                </span>

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Perfil
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Cerrar Sesión
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    {user.role === 'admin' ? (
                        <div className="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink href={route('admin.dashboard')} active={route().current('admin.dashboard')}>
                                Dashboard
                            </ResponsiveNavLink>
                            <ResponsiveNavLink href={route('admin.users')} active={route().current('admin.users')}>
                                Usuarios
                            </ResponsiveNavLink>
                        </div>
                    ) : user.role === 'student' ? (
                        <div className="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink
                                href={route('student.dashboard')}
                                active={route().current('student.dashboard')}
                            >
                                Panel de Usuario
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                href={route('student.solicitudes')}
                                active={route().current('student.solicitudes')}
                            >
                                Mis Solicitudes
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                href={route('student.notifications')}
                                active={route().current('student.notifications')}
                            >
                                <div className="flex items-center justify-between">
                                    <span>Notificaciones</span>
                                    {contadorNoLeidas > 0 && (
                                        <span className="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">
                                            {contadorNoLeidas > 9 ? '9+' : contadorNoLeidas}
                                        </span>
                                    )}
                                </div>
                            </ResponsiveNavLink>
                        </div>
                    ) : (
                        <div className="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink
                                href={route(redirectTo)}
                                active={route().current(redirectTo)}
                            >
                                Panel de Usuario
                            </ResponsiveNavLink>
                        </div>
                    )}
                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800 flex items-center">
                                {user.name}
                                <ProfileIncompleteIcon className="ml-2" />
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Cerrar Sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
            
            {/* Toast notifications container */}
            <ToastContainer 
                position="top-right"
                autoClose={4000}
                hideProgressBar={false}
                newestOnTop
                closeOnClick
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme="light"
            />
        </div>
    );
}
