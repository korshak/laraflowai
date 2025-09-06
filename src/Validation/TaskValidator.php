<?php

namespace LaraFlowAI\Validation;

use Illuminate\Support\Facades\Validator;

class TaskValidator
{
    /**
     * Validate task configuration
     */
    public static function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'description' => 'required|string|max:2000',
            'agent' => 'nullable|string|max:255',
            'tool_inputs' => 'nullable|array',
            'context' => 'nullable|array',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw \LaraFlowAI\Exceptions\LaraFlowAIException::validationFailed($validator->errors()->all());
        }

        return $validator->validated();
    }
}
