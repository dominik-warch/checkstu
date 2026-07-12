<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\RecurrenceType;
use App\Models\Task;
use App\Support\Recurrence\RruleExpander;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', Rule::in([0, 1, 2, 3])],
            'is_private' => ['boolean'],
            // A private task must have an assignee — otherwise nobody could ever see it.
            'default_assignee_id' => [Rule::requiredIf($this->boolean('is_private')), 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'recurrence_type' => ['nullable', Rule::enum(RecurrenceType::class)],

            'rrule' => [
                'required_if:recurrence_type,'.RecurrenceType::Rrule->value,
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null && ! app(RruleExpander::class)->isValid($value)) {
                        $fail('Diese Wiederholungsregel ist ungültig.');
                    }
                },
            ],
            'anchor_date' => ['required_if:recurrence_type,'.RecurrenceType::Rrule->value, 'nullable', 'date'],

            'relative_interval_days' => [
                'required_if:recurrence_type,'.RecurrenceType::Relative->value,
                'nullable', 'integer', 'min:1', 'max:365',
            ],

            'explicit_dates' => [
                'required_if:recurrence_type,'.RecurrenceType::ExplicitDates->value,
                'nullable', 'array', 'min:1',
            ],
            'explicit_dates.*' => ['date'],

            'recurrence_ends_on' => ['nullable', 'date'],
        ];
    }
}
