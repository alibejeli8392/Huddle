import { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import { SearchIcon } from '@/Components/ui/Icons';
import MemberCard from '@/Components/social/MemberCard';
import AppLayout from '@/Layouts/AppLayout';
import { useHuddle } from '@/context/HuddleContext';

export default function Index() {
    const { state, viewer } = useHuddle();
    const [query, setQuery] = useState('');

    const members = useMemo(() => {
        const q = query.trim().toLowerCase();

        return state.members.filter((member) => {
            if (viewer && member.id === viewer.id) {
                return false;
            }

            if (!q) {
                return true;
            }

            return (
                member.name.toLowerCase().includes(q) ||
                member.title.toLowerCase().includes(q) ||
                member.department.toLowerCase().includes(q)
            );
        });
    }, [state.members, viewer, query]);

    return (
        <>
            <Head title="Members" />

            <div className="mx-auto w-full max-w-[980px] px-4 py-6 lg:px-10 lg:py-10">
                <header className="mb-8">
                    <h1 className="text-xl font-semibold tracking-tight">Members</h1>
                    <p className="mt-1 text-sm text-muted">
                        Everyone at the company. Find a teammate and say hello.
                    </p>
                    <div className="relative mt-5 max-w-md">
                        <SearchIcon
                            size={16}
                            className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted"
                        />
                        <input
                            type="search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search people, roles, teams"
                            className="h-10 w-full rounded-lg border border-line bg-wash pr-3 pl-9 text-sm text-ink shadow-none focus:border-black focus:ring-black"
                        />
                    </div>
                </header>

                {members.length === 0 ? (
                    <p className="py-16 text-center text-sm text-muted">No one matches that search.</p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                        {members.map((user) => (
                            <MemberCard key={user.id} user={user} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
