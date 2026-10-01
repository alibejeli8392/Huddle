// New design shell for Home, Members, Friends, Messages, and Profile (/me).
// Account settings (/profile) still use AuthenticatedLayout. Both stay until that Breeze screen is restyled.
import { usePage } from '@inertiajs/react';
import Navbar from '@/Components/social/Navbar';
import { HuddleProvider } from '@/context/HuddleContext';

export default function AppLayout({ children }) {
    const { url } = usePage();
    const path = url.split('?')[0];
    const isMessages = path.startsWith('/messages');

    return (
        <HuddleProvider>
            <div className="huddle-app min-h-screen bg-white">
                <Navbar />
                <main className={isMessages ? 'lg:ml-[244px]' : 'pb-20 lg:ml-[244px] lg:pb-0'}>
                    <div key={path} className="huddle-rise">
                        {children}
                    </div>
                </main>
            </div>
        </HuddleProvider>
    );
}
