<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SharePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->route('post') instanceof Post;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.type' => ['required', Rule::in(['conversation', 'friend'])],
            'targets.*.id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'targets.required' => 'Choose someone to share with.',
            'targets.min' => 'Choose someone to share with.',
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

                foreach ($this->input('targets', []) as $index => $target) {
                    $id = (int) ($target['id'] ?? 0);

                    if (($target['type'] ?? null) === 'conversation') {
                        $conversation = Conversation::query()->find($id);

                        if (! $conversation instanceof Conversation || ! $user->can('view', $conversation)) {
                            $validator->errors()->add("targets.{$index}.id", 'Choose a conversation you belong to.');
                        }

                        continue;
                    }

                    $friend = User::query()->find($id);

                    if (! $friend instanceof User || ! $user->isFriendWith($friend)) {
                        $validator->errors()->add("targets.{$index}.id", 'You can only share with accepted friends.');
                    }
                }
            },
        ];
    }
}
