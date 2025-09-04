import { Link } from '@inertiajs/react';

export default function ApplicationLogo() {
    return (

        <div className="flex justify-center py-4">
            <Link href="/">
                <img 
                    src="images/logo_unab.png" 
                    className="h-12 w-auto" 
                    alt="Logo" 
                />
            </Link>
        </div>
        // <img
        //     {...props}
        //     src="/images/favicons/apple-touch-icon.png" // Replace with the correct path to your logo
        //     alt="Application Logo"
        // />
    );
}
