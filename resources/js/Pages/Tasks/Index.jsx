import { Head, router, useForm, usePage } from '@inertiajs/react';
import Button from '@/Components/ui/Button';
import InputError from '@/Components/breeze/InputError';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/utils/cn';

const fieldClass =
    'h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm text-ink shadow-none focus:border-black focus:ring-black';

function formatTime(value) {
    if (!value) {
        return null;
    }

    const [hours, minutes] = value.split(':').map(Number);

    if (Number.isNaN(hours) || Number.isNaN(minutes)) {
        return value;
    }

    const period = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;

    return `${hour12}:${String(minutes).padStart(2, '0')} ${period}`;
}

function formatDateLabel(date, today) {
    if (date === today) {
        return 'Today';
    }

    return new Date(`${date}T12:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}

function TaskRow({ task, showProgress = false, showDelete = false, showDone = false }) {
    const completed = Boolean(task.completed);

    const toggle = () => {
        router.post(route('tasks.complete', task.id), {}, { preserveScroll: true });
    };

    const remove = () => {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    };

    return (
        <li
            className={cn(
                'flex items-start gap-3 border-b border-line py-4 transition-opacity',
                completed && showDone && 'opacity-45',
            )}
        >
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <h3
                        className={cn(
                            'text-[15px] font-semibold tracking-tight text-ink',
                            completed && showDone && 'line-through',
                        )}
                    >
                        {task.title}
                    </h3>
                    {task.dueTime && (
                        <span className="text-xs font-medium text-muted">{formatTime(task.dueTime)}</span>
                    )}
                </div>
                {task.details && <p className="mt-1 text-sm leading-relaxed text-muted">{task.details}</p>}
                {showProgress && (
                    <p className="mt-2 text-xs text-muted">
                        {task.completedCount} of {task.employeeCount} done
                    </p>
                )}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                {showDone && (
                    <Button type="button" size="sm" variant={completed ? 'secondary' : 'primary'} onClick={toggle}>
                        {completed ? 'Undo' : 'Done'}
                    </Button>
                )}
                {showDelete && (
                    <Button type="button" size="sm" variant="secondary" onClick={remove}>
                        Delete
                    </Button>
                )}
            </div>
        </li>
    );
}

function Empty({ children }) {
    return <p className="py-10 text-center text-sm text-muted">{children}</p>;
}

export default function Index({
    role,
    department,
    today,
    todayTasks = [],
    upcomingTasks = [],
    progress = null,
}) {
    const { errors } = usePage().props;
    const isManager = role === 'manager';

    const form = useForm({
        title: '',
        details: '',
        due_date: today,
        due_time: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('tasks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('title', 'details', 'due_time');
                form.setData('due_date', today);
            },
        });
    };

    return (
        <>
            <Head title="Tasks" />

            <div className="mx-auto w-full max-w-[720px] px-4 py-6 lg:py-10">
                <div className="mb-6">
                    <h1 className="text-xl font-semibold tracking-tight">Tasks</h1>
                    <p className="mt-1 text-sm text-muted">
                        {department ? `${department} · daily work` : 'Daily department work'}
                    </p>
                </div>

                {isManager && (
                    <section className="mb-10 border-b border-line pb-8">
                        <h2 className="mb-4 text-sm font-semibold tracking-wide uppercase">New task</h2>
                        <form onSubmit={submit} className="space-y-3">
                            <div>
                                <label htmlFor="title" className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    Title
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    value={form.data.title}
                                    maxLength={120}
                                    className={fieldClass}
                                    onChange={(event) => form.setData('title', event.target.value)}
                                />
                                <InputError message={errors?.title || form.errors.title} className="mt-2" />
                            </div>
                            <div>
                                <label htmlFor="details" className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    Details
                                </label>
                                <textarea
                                    id="details"
                                    rows={3}
                                    value={form.data.details}
                                    className="w-full rounded-lg border border-line bg-wash px-3 py-2 text-sm text-ink shadow-none focus:border-black focus:ring-black"
                                    onChange={(event) => form.setData('details', event.target.value)}
                                />
                                <InputError message={errors?.details || form.errors.details} className="mt-2" />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label htmlFor="due_date" className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                        Date
                                    </label>
                                    <input
                                        id="due_date"
                                        type="date"
                                        value={form.data.due_date}
                                        className={fieldClass}
                                        onChange={(event) => form.setData('due_date', event.target.value)}
                                    />
                                    <InputError message={errors?.due_date || form.errors.due_date} className="mt-2" />
                                </div>
                                <div>
                                    <label htmlFor="due_time" className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                        Time
                                    </label>
                                    <input
                                        id="due_time"
                                        type="time"
                                        value={form.data.due_time}
                                        className={fieldClass}
                                        onChange={(event) => form.setData('due_time', event.target.value)}
                                    />
                                    <InputError message={errors?.due_time || form.errors.due_time} className="mt-2" />
                                </div>
                            </div>
                            <p className="text-xs text-muted">Posted to {department || 'your department'}.</p>
                            <Button type="submit" size="md" disabled={form.processing || !form.data.title.trim()}>
                                Add task
                            </Button>
                        </form>
                    </section>
                )}

                {!isManager && progress && (
                    <p className="mb-4 text-sm text-muted">
                        {progress.done} of {progress.total} done
                    </p>
                )}

                <section className="mb-10">
                    <h2 className="mb-2 text-sm font-semibold tracking-wide uppercase">
                        {isManager ? 'Today' : 'Today\'s tasks'}
                    </h2>
                    {todayTasks.length === 0 ? (
                        <Empty>
                            {isManager
                                ? 'No tasks for today yet. Add one above.'
                                : 'Nothing on the list for today. Enjoy the quiet.'}
                        </Empty>
                    ) : (
                        <ul className="border-t border-line">
                            {todayTasks.map((task) => (
                                <TaskRow
                                    key={task.id}
                                    task={task}
                                    showDone={!isManager}
                                    showProgress={isManager}
                                    showDelete={isManager && task.canDelete}
                                />
                            ))}
                        </ul>
                    )}
                </section>

                {isManager && (
                    <section>
                        <h2 className="mb-2 text-sm font-semibold tracking-wide uppercase">Upcoming</h2>
                        {upcomingTasks.length === 0 ? (
                            <Empty>No upcoming tasks.</Empty>
                        ) : (
                            <ul className="border-t border-line">
                                {upcomingTasks.map((task) => (
                                    <li key={task.id} className="border-b border-line py-4">
                                        <p className="mb-2 text-xs font-semibold tracking-wide text-muted uppercase">
                                            {formatDateLabel(task.dueDate, today)}
                                        </p>
                                        <div className="flex items-start gap-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                                    <h3 className="text-[15px] font-semibold tracking-tight text-ink">
                                                        {task.title}
                                                    </h3>
                                                    {task.dueTime && (
                                                        <span className="text-xs font-medium text-muted">
                                                            {formatTime(task.dueTime)}
                                                        </span>
                                                    )}
                                                </div>
                                                {task.details && (
                                                    <p className="mt-1 text-sm leading-relaxed text-muted">{task.details}</p>
                                                )}
                                                <p className="mt-2 text-xs text-muted">
                                                    {task.completedCount} of {task.employeeCount} done
                                                </p>
                                            </div>
                                            {task.canDelete && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="secondary"
                                                    onClick={() =>
                                                        router.delete(route('tasks.destroy', task.id), {
                                                            preserveScroll: true,
                                                        })
                                                    }
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                )}
            </div>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
