<?php

namespace LaraFlowAI\Tools;

use Illuminate\Support\Facades\Storage;

class FilesystemTool extends BaseTool
{
    public function __construct(array $config = [])
    {
        parent::__construct(
            'filesystem',
            'Perform filesystem operations',
            array_merge([
                'input_schema' => [
                    'operation' => ['required' => true, 'type' => 'string'],
                    'path' => ['required' => true, 'type' => 'string'],
                    'content' => ['required' => false, 'type' => 'string'],
                    'disk' => ['required' => false, 'type' => 'string', 'default' => 'local'],
                ]
            ], $config)
        );
    }

    public function run(array $input): mixed
    {
        if (!$this->validateInput($input)) {
            throw new \InvalidArgumentException('Invalid input for Filesystem tool');
        }

        $operation = strtolower($input['operation']);
        $path = $input['path'];
        $content = $input['content'] ?? null;
        $disk = $input['disk'] ?? 'local';

        try {
            $storage = Storage::disk($disk);

            switch ($operation) {
                case 'read':
                    $result = $storage->get($path);
                    break;
                case 'write':
                    $result = $storage->put($path, $content);
                    break;
                case 'append':
                    $result = $storage->append($path, $content);
                    break;
                case 'exists':
                    $result = $storage->exists($path);
                    break;
                case 'delete':
                    $result = $storage->delete($path);
                    break;
                case 'copy':
                    $destination = $input['destination'] ?? null;
                    if (!$destination) {
                        throw new \InvalidArgumentException('Destination path required for copy operation');
                    }
                    $result = $storage->copy($path, $destination);
                    break;
                case 'move':
                    $destination = $input['destination'] ?? null;
                    if (!$destination) {
                        throw new \InvalidArgumentException('Destination path required for move operation');
                    }
                    $result = $storage->move($path, $destination);
                    break;
                case 'list':
                    $result = $storage->files($path);
                    break;
                case 'directories':
                    $result = $storage->directories($path);
                    break;
                case 'size':
                    $result = $storage->size($path);
                    break;
                case 'last_modified':
                    $result = $storage->lastModified($path);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported filesystem operation: {$operation}");
            }

            $this->logExecution($input, $result);
            return $result;

        } catch (\Exception $e) {
            $this->logExecution($input, ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
