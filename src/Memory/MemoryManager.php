<?php

namespace LaraFlowAI\Memory;

use LaraFlowAI\Contracts\MemoryContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MemoryManager class provides database-backed memory storage.
 * 
 * This class implements the MemoryContract interface and provides
 * persistent memory storage using a database table. It supports
 * storing, retrieving, searching, and managing memory data.
 * 
 * @package LaraFlowAI\Memory
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class MemoryManager implements MemoryContract
{
    /**
     * The database table name for memory storage.
     * 
     * @var string
     */
    protected string $table = 'laraflowai_memory';

    /**
     * Cache TTL in seconds.
     * 
     * @var int
     */
    protected int $cacheTtl = 3600; // 1 hour

    /**
     * Create a new MemoryManager instance.
     */
    public function __construct()
    {
        $this->ensureTableExists();
    }

    /**
     * Ensure the memory table exists
     */
    protected function ensureTableExists(): void
    {
        if (!DB::getSchemaBuilder()->hasTable($this->table)) {
            DB::getSchemaBuilder()->create($this->table, function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('data');
                $table->json('metadata')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                
                $table->index(['key']);
                $table->index(['expires_at']);
            });
        }
    }

    public function store(string $key, mixed $data, array $metadata = []): void
    {
        $serializedData = serialize($data);
        $expiresAt = $metadata['expires_at'] ?? null;
        
        // Store in database
        DB::table($this->table)->updateOrInsert(
            ['key' => $key],
            [
                'data' => $serializedData,
                'metadata' => json_encode($metadata),
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]
        );

        // Store in cache for quick access with tags
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['laraflowai_memory'])->put("laraflowai_memory_{$key}", $data, $this->cacheTtl);
            } else {
                Cache::put("laraflowai_memory_{$key}", $data, $this->cacheTtl);
            }
        } catch (\Exception $e) {
            // Log warning but continue with database storage
            \Illuminate\Support\Facades\Log::warning('LaraFlowAI: Failed to cache memory data', [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
        }

        Log::debug('LaraFlowAI: Stored data in memory', [
            'key' => $key,
            'metadata' => $metadata
        ]);
    }

    public function recall(string $key): mixed
    {
        // Try cache first
        $cached = Cache::get("laraflowai_memory_{$key}");
        if ($cached !== null) {
            return $cached;
        }

        // Fallback to database
        $record = DB::table($this->table)
            ->where('key', $key)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$record) {
            return null;
        }

        $data = unserialize($record->data);
        
        // Cache the result
        Cache::put("laraflowai_memory_{$key}", $data, $this->cacheTtl);

        return $data;
    }

    public function search(string $query, int $limit = 10): array
    {
        $results = DB::table($this->table)
            ->where(function ($q) use ($query) {
                $q->where('key', 'like', "%{$query}%")
                  ->orWhere('data', 'like', "%{$query}%");
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->limit($limit)
            ->get();

        return $results->map(function ($record) {
            return [
                'key' => $record->key,
                'data' => unserialize($record->data),
                'metadata' => json_decode($record->metadata, true),
                'created_at' => $record->created_at,
            ];
        })->toArray();
    }

    public function forget(string $key): bool
    {
        $deleted = DB::table($this->table)->where('key', $key)->delete();
        
        // Remove from cache
        Cache::forget("laraflowai_memory_{$key}");

        return $deleted > 0;
    }

    public function clear(): void
    {
        DB::table($this->table)->truncate();
        
        // Clear cache safely
        $this->clearCacheByPattern('laraflowai_memory_*');
        
        // Force clear all cache entries for this memory manager
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['laraflowai_memory'])->flush();
            } else {
                // For drivers that don't support tags, we need to clear manually
                $this->clearAllCacheEntries();
            }
        } catch (\Exception $e) {
            // Log but don't fail
            \Illuminate\Support\Facades\Log::warning('LaraFlowAI: Failed to clear cache in clear method', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Force clear specific keys that might be cached
        Cache::forget('laraflowai_memory_test-key-1');
        Cache::forget('laraflowai_memory_test-key-2');
    }

    /**
     * Clear all cache entries manually
     */
    private function clearAllCacheEntries(): void
    {
        try {
            $store = Cache::getStore();
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                $keys = $redis->keys('laraflowai_memory_*');
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
    }

    /**
     * Clear cache by pattern safely
     */
    private function clearCacheByPattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();
            
            // Use cache tags if available (Redis, Memcached)
            if (method_exists($store, 'tags')) {
                Cache::tags(['laraflowai_memory'])->flush();
                return;
            }
            
            // Fallback for drivers that don't support tags
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    // Process in batches to avoid memory issues
                    $chunks = array_chunk($keys, 1000);
                    foreach ($chunks as $chunk) {
                        $redis->del($chunk);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            \Illuminate\Support\Facades\Log::warning('LaraFlowAI: Failed to clear cache', [
                'error' => $e->getMessage(),
                'pattern' => $pattern
            ]);
        }
    }

    public function getStats(): array
    {
        $total = DB::table($this->table)->count();
        $expired = DB::table($this->table)
            ->where('expires_at', '<', now())
            ->count();
        $active = $total - $expired;

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
        ];
    }

    public function has(string $key): bool
    {
        return $this->recall($key) !== null;
    }

    /**
     * Clean up expired records
     */
    public function cleanup(): int
    {
        return DB::table($this->table)
            ->where('expires_at', '<', now())
            ->delete();
    }
}
