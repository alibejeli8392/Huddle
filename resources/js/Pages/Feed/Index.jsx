import { useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import Button from '@/Components/ui/Button';
import PostCard from '@/Components/social/PostCard';
import AppLayout from '@/Layouts/AppLayout';
import { useHuddle } from '@/context/HuddleContext';

export default function Index({ posts = [] }) {
    const { viewer } = useHuddle();
    const { errors } = usePage().props;
    const [text, setText] = useState('');
    const [posting, setPosting] = useState(false);
    const fieldRef = useRef(null);

    const growField = (field) => {
        field.style.height = 'auto';
        field.style.height = `${field.scrollHeight}px`;
    };

    if (!viewer) {
        return null;
    }

    const submit = (event) => {
        event.preventDefault();

        if (!text.trim() || posting) {
            return;
        }

        router.post(
            route('posts.store'),
            { body: text },
            {
                preserveScroll: true,
                onStart: () => setPosting(true),
                onFinish: () => setPosting(false),
                onSuccess: () => {
                    setText('');

                    if (fieldRef.current) {
                        fieldRef.current.style.height = 'auto';
                    }
                },
            },
        );
    };

    return (
        <>
            <Head title="Home" />

            <div className="mx-auto w-full max-w-[630px] px-4 py-6 lg:py-10">
                <form onSubmit={submit} className="mb-2 border-b border-line pb-5">
                    <div className="flex gap-3">
                        <Avatar src={viewer.avatar} name={viewer.name} size="sm" />
                        <div className="flex-1">
                            <textarea
                                ref={fieldRef}
                                value={text}
                                onChange={(event) => {
                                    setText(event.target.value);
                                    growField(event.target);
                                }}
                                placeholder="Share something with the company…"
                                rows={2}
                                className="min-h-[3rem] w-full overflow-hidden border-0 bg-transparent p-0 text-[15px] leading-relaxed text-ink shadow-none placeholder:text-muted focus:outline-none focus:ring-0"
                            />
                            {errors?.body && <p className="mt-1 text-sm text-heart">{errors.body}</p>}
                            <div className="mt-2 flex items-center justify-between">
                                <span className="text-xs text-muted">Public · Everyone at work</span>
                                <Button type="submit" size="sm" disabled={!text.trim() || posting}>
                                    Post
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>

                <div>
                    {posts.map((post) => (
                        <PostCard key={post.id} post={post} />
                    ))}
                </div>
            </div>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
