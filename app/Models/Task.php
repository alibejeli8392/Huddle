<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['created_by', 'department', 'title', 'details', 'due_date', 'due_time'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TaskCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeForDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeDueOn(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('due_date', $date);
    }

    /**
     * Tasks with a due time first (ascending), then tasks without a time.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOrderByDueTime(Builder $query): Builder
    {
        return $query
            ->orderByRaw('due_time is null')
            ->orderBy('due_time')
            ->orderBy('id');
    }

    public function isCompletedBy(User $user): bool
    {
        if ($this->relationLoaded('completions')) {
            return $this->completions->contains(fn (TaskCompletion $row): bool => (int) $row->user_id === (int) $user->id);
        }

        if (array_key_exists('completed_by_viewer', $this->attributes)) {
            return (bool) $this->attributes['completed_by_viewer'];
        }

        return $this->completions()->where('user_id', $user->id)->exists();
    }

    /**
     * Shape used by the Tasks screen.
     *
     * @return array{
     *     id: int,
     *     title: string,
     *     details: string|null,
     *     dueDate: string,
     *     dueTime: string|null,
     *     department: string,
     *     completed: bool,
     *     completedCount: int,
     *     employeeCount: int,
     *     canDelete: bool
     * }
     */
    public function toListItem(User $viewer, int $employeeCount): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'details' => $this->details,
            'dueDate' => $this->due_date?->toDateString() ?? '',
            'dueTime' => $this->formattedDueTime(),
            'department' => $this->department,
            'completed' => $this->isCompletedBy($viewer),
            'completedCount' => (int) ($this->completions_count ?? $this->completions()->count()),
            'employeeCount' => $employeeCount,
            'canDelete' => $viewer->can('delete', $this),
        ];
    }

    public function formattedDueTime(): ?string
    {
        if ($this->due_time === null || $this->due_time === '') {
            return null;
        }

        return Carbon::parse($this->due_time)->format('H:i');
    }
}
