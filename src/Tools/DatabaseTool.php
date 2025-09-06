<?php

namespace LaraFlowAI\Tools;

use Illuminate\Support\Facades\DB;

class DatabaseTool extends BaseTool
{
    public function __construct(array $config = [])
    {
        parent::__construct(
            'database',
            'Execute database queries',
            array_merge([
                'input_schema' => [
                    'query' => ['required' => true, 'type' => 'string'],
                    'bindings' => ['required' => false, 'type' => 'array', 'default' => []],
                    'type' => ['required' => false, 'type' => 'string', 'default' => 'select'],
                ]
            ], $config)
        );
    }

    public function run(array $input): mixed
    {
        if (!$this->validateInput($input)) {
            throw new \InvalidArgumentException('Invalid input for Database tool');
        }

        $query = $input['query'];
        $bindings = $input['bindings'] ?? [];
        $type = strtolower($input['type'] ?? 'select');

        try {
            switch ($type) {
                case 'select':
                    $result = DB::select($query, $bindings);
                    break;
                case 'insert':
                    $result = DB::insert($query, $bindings);
                    break;
                case 'update':
                    $result = DB::update($query, $bindings);
                    break;
                case 'delete':
                    $result = DB::delete($query, $bindings);
                    break;
                case 'statement':
                    $result = DB::statement($query, $bindings);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported query type: {$type}");
            }

            $this->logExecution($input, $result);
            return $result;

        } catch (\Exception $e) {
            $this->logExecution($input, ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
