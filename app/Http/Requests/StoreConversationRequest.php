<?php

namespace App\Http\Requests;

use App\ConversationType;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $direct = $this->input('type') === ConversationType::Direct->value;
        $group = $this->input('type') === ConversationType::Group->value;

        return [
            'type' => ['required', Rule::enum(ConversationType::class)],
            'friend_id' => [
                Rule::requiredIf($direct),
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'name' => [
                Rule::requiredIf($group),
                'nullable',
                'string',
                'max:60',
            ],
            'member_ids' => [
                Rule::requiredIf($group),
                'nullable',
                'array',
                'min:2',
            ],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'friend_id.required' => 'Choose a friend to message.',
            'name.required' => 'Give the group a name.',
            'name.max' => 'The group name must be 60 characters or fewer.',
            'member_ids.required' => 'Choose at least two friends.',
            'member_ids.min' => 'Choose at least two friends.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                if ($this->input('type') === ConversationType::Direct->value) {
                    $friend = User::query()->find($this->integer('friend_id'));

                    if (! $friend instanceof User || ! $user->isFriendWith($friend)) {
                        $validator->errors()->add('friend_id', 'You can only message accepted friends.');
                    }

                    return;
                }

                $members = User::query()->whereKey($this->input('member_ids', []))->get();

                foreach ($members as $member) {
                    if (! $user->isFriendWith($member)) {
                        $validator->errors()->add('member_ids', 'Every member must be an accepted friend.');

                        return;
                    }
                }
            },
        ];
    }
}
