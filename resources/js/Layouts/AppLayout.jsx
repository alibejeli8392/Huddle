// Shell for every signed-in screen: Home, People, Friends, Messages, Tasks, and Profile.
// Profile includes the account settings tab. Guest auth screens still use GuestLayout.
import { usePage } from '@inertiajs/react';
import Navbar from '@/Components/social/Navbar';

export default function AppLayout({ children }) {
    const { url } = usePage();
    const path = url.split('?')[0];
    const isMessages = path.startsWith('/messages');

    return (
        <div className="huddle-app min-h-screen bg-white">
            <Navbar />
            <main className={isMessages ? 'lg:ml-[244px]' : 'pb-20 lg:ml-[244px] lg:pb-0'}>
                <div key={path} className="huddle-rise">
                    {children}
                </div>
            </main>
        </div>
    );
}
