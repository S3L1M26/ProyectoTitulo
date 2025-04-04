import ApplicationLogo from '@/Components/ApplicationLogo';

export default function GuestLayout({ children, onSubmit, headerMsg, footerElements }) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50">
            <div className="bg-white shadow-lg rounded-lg max-w-sm w-full max-h-[50%] flex flex-col">
                <div className="pt-4 px-4 pb-0 mb-4 border-b border-transparent rounded-tl-sm rounded-tr-sm text-center">
                    <ApplicationLogo />
                </div>
                
                {headerMsg}

                <form onSubmit={onSubmit} className="px-6 pb-6 flex-grow flex flex-col">
                    <div className="space-y-4">
                        {children}
                    </div>

                    <div className="mt-6">
                        {footerElements}
                    </div>
                </form>
            </div>
        </div>
    );
}
