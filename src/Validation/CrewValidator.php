<?php

namespace LaraFlowAI\Validation;

use Illuminate\Support\Facades\Validator;

class CrewValidator
{
    /**
     * Validate crew configuration
     */
    public static function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'execution_mode' => 'nullable|string|in:sequential,parallel',
            'max_parallel_tasks' => 'nullable|integer|min:1|max:100',
            'timeout' => 'nullable|integer|min:1|max:3600',
            'agents' => 'nullable|array',
            'agents.*.role' => 'required_with:agents|string|max:255',
            'agents.*.goal' => 'required_with:agents|string|max:1000',
            'tasks' => 'nullable|array',
            'tasks.*.description' => 'required_with:tasks|string|max:2000',
        ]);

        if ($validator->fails()) {
            throw \LaraFlowAI\Exceptions\LaraFlowAIException::validationFailed($validator->errors()->all());
        }

        return $validator->validated();
    }
}
