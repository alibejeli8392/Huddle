import { Head } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import FriendButton from '@/Components/social/FriendButton';
import AppLayout from '@/Layouts/AppLayout';

function Empty({ children }) {
    return <p className="py-6 text-sm text-muted">{children}</p>;
}

export default function Index({ incomingRequests = [], outgoingRequests = [], friends = [] }) {
    return (
        <>
            <Head title="Friends" />

            <div className="mx-auto w-full max-w-[720px] px-4 py-6 lg:py-10">
                <h1 className="mb-8 text-xl font-semibold tracking-tight">Friends</h1>

                <section className="mb-10">
                    <h2 className="mb-4 text-sm font-semibold tracking-wide uppercase">
                        Friend Requests
                        {incomingRequests.length > 0 && (
                            <span className="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-black px-1.5 text-[11px] text-white">
                                {incomingRequests.length}
                            </span>
                        )}
                    </h2>
                    {incomingRequests.length === 0 ? (
                        <Empty>No pending requests.</Empty>
                    ) : (
                        <ul className="divide-y divide-line border-t border-b border-line">
                            {incomingRequests.map((user) => (
                                <li key={user.id}>
                                    <div className="flex items-center gap-3 py-3">
                                        <Avatar src={user.avatar} name={user.name} size="md" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold">{user.name}</p>
                                            <p className="truncate text-xs text-muted">{user.title}</p>
                                        </div>
                                        <FriendButton user={user} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="mb-10">
                    <h2 className="mb-4 text-sm font-semibold tracking-wide uppercase">Your Friends</h2>
                    {friends.length === 0 ? (
                        <Empty>You haven&apos;t added any friends yet.</Empty>
                    ) : (
                        <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {friends.map((user) => (
                                <li
                                    key={user.id}
                                    className="flex items-center gap-3 rounded-xl border border-line px-3 py-3"
                                >
                                    <Avatar src={user.avatar} name={user.name} size="md" />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-semibold">{user.name}</p>
                                        <p className="truncate text-xs text-muted">{user.title}</p>
                                    </div>
                                    <FriendButton user={user} />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section>
                    <h2 className="mb-4 text-sm font-semibold tracking-wide uppercase">Requests Sent</h2>
                    {outgoingRequests.length === 0 ? (
                        <Empty>No outgoing requests.</Empty>
                    ) : (
                        <ul className="divide-y divide-line border-t border-b border-line">
                            {outgoingRequests.map((user) => (
                                <li key={user.id} className="flex items-center gap-3 py-3">
                                    <Avatar src={user.avatar} name={user.name} size="md" />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-semibold">{user.name}</p>
                                        <p className="truncate text-xs text-muted">{user.title}</p>
                                    </div>
                                    <FriendButton user={user} />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
