<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:150',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in(['todo', 'in_progress', 'done']),
            ],

            'priority' => [
                'sometimes',
                'required',
                Rule::in(['low', 'medium', 'high']),
            ],

            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }

                    $workspace = $this->route('workspace');

                    if (! $workspace instanceof Workspace) {
                        $fail('The workspace is invalid.');
                        return;
                    }

                    $isMember = $workspace->members()
                        ->where('users.id', $value)
                        ->exists();

                    if (! $isMember) {
                        $fail(
                            'The assigned user must be a member of this workspace.'
                        );
                    }
                },
            ],

            'due_date' => [
                'sometimes',
                'nullable',
                'date',
            ],
        ];
    }
}
