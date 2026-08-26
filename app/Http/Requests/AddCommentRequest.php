<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CaseModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('comment', $this->route('case'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CaseModel $case */
        $case = $this->route('case');

        return [
            'comment' => ['required', 'string', 'min:3', 'max:5000'],

            // The department the author is speaking for. Must be one of the
            // departments involved in this case, and one the author belongs to.
            'department_id' => [
                'required',
                'integer',
                Rule::in($this->permittedActingDepartmentIds($case)),
            ],

            'forwarded_to_department_id' => [
                'nullable',
                'integer',
                'different:department_id',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],

            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => AttachmentRules::forUploadedFile(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'department_id.in' => 'You may only record a remark as a department you are assigned to on this case.',
            'forwarded_to_department_id.different' => 'A case cannot be forwarded to the department that is recording the remark.',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function permittedActingDepartmentIds(CaseModel $case): array
    {
        $user = $this->user();

        if ($user->isSuperAdmin()) {
            return $case->involvedDepartmentIds();
        }

        return array_values(array_intersect(
            $user->accessibleDepartmentIds(),
            $case->involvedDepartmentIds(),
        ));
    }
}
