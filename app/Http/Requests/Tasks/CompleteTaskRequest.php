<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use App\Models\TaskOccurrence;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        /** @var TaskOccurrence $occurrence */
        $occurrence = $this->route('occurrence');

        $completedById = $this->input('completed_by_user_id');

        // Attributing the completion to someone else is admin-only, and must
        // still pass the normal `complete` check — an admin can't use "behalf"
        // attribution as a backdoor into a private task they can't otherwise see.
        if ($completedById !== null && (int) $completedById !== $user->id) {
            return $user->can('completeOnBehalf', Task::class) && $user->can('complete', $occurrence->task);
        }

        return $user->can('complete', $occurrence->task);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'completed_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
