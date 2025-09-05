<?php

namespace LaraFlowAI\Validation;

use Illuminate\Support\Facades\Validator;

class AgentValidator
{
    /**
     * Validate agent configuration
     */
    public static function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'role' => 'required|string|max:255',
            'goal' => 'required|string|max:1000',
            'provider' => 'nullable|string|max:255',
            'config' => 'nullable|array',
            'config.prompt_template' => 'nullable|string|max:255',
            'config.llm_options' => 'nullable|array',
            'config.memory_search_limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw \LaraFlowAI\Exceptions\LaraFlowAIException::validationFailed($validator->errors()->all());
        }

        return $validator->validated();
    }
}
