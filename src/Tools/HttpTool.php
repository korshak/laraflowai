<?php

namespace LaraFlowAI\Tools;

use Illuminate\Support\Facades\Http;

class HttpTool extends BaseTool
{
    public function __construct(array $config = [])
    {
        parent::__construct(
            'http',
            'Make HTTP requests to external APIs',
            array_merge([
                'input_schema' => [
                    'url' => ['required' => true, 'type' => 'string'],
                    'method' => ['required' => false, 'type' => 'string', 'default' => 'GET'],
                    'headers' => ['required' => false, 'type' => 'array', 'default' => []],
                    'data' => ['required' => false, 'type' => 'array', 'default' => []],
                    'timeout' => ['required' => false, 'type' => 'integer', 'default' => 30],
                ]
            ], $config)
        );
    }

    public function run(array $input): mixed
    {
        if (!$this->validateInput($input)) {
            throw new \InvalidArgumentException('Invalid input for HTTP tool');
        }

        $url = $input['url'];
        $method = strtoupper($input['method'] ?? 'GET');
        $headers = $input['headers'] ?? [];
        $data = $input['data'] ?? [];
        $timeout = $input['timeout'] ?? 30;

        try {
            $client = Http::withHeaders($headers)->timeout($timeout);

            switch ($method) {
                case 'GET':
                    $response = $client->get($url, $data);
                    break;
                case 'POST':
                    $response = $client->post($url, $data);
                    break;
                case 'PUT':
                    $response = $client->put($url, $data);
                    break;
                case 'DELETE':
                    $response = $client->delete($url, $data);
                    break;
                case 'PATCH':
                    $response = $client->patch($url, $data);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
            }

            $result = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];

            $this->logExecution($input, $result);
            return $result;

        } catch (\Exception $e) {
            $this->logExecution($input, ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
