import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50">
            <div className="w-full max-w-md px-6 py-8 bg-white rounded-lg shadow-md">
                <div className="flex justify-center mb-8">
                    <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                    </Link>
                </div>
                {children}
            </div>
        </div>
    );
}
