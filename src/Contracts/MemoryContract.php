<?php

namespace LaraFlowAI\Contracts;

/**
 * MemoryContract interface defines the contract for memory systems.
 * 
 * This interface ensures that all memory implementations provide
 * the same basic functionality for storing, retrieving, searching,
 * and managing data in memory.
 * 
 * @package LaraFlowAI\Contracts
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
interface MemoryContract
{
    /**
     * Store data in memory.
     * 
     * @param string $key The memory key
     * @param mixed $data The data to store
     * @param array<string, mixed> $metadata Optional metadata
     */
    public function store(string $key, mixed $data, array $metadata = []): void;

    /**
     * Retrieve data from memory.
     * 
     * @param string $key The memory key
     * @return mixed The stored data or null if not found
     */
    public function recall(string $key): mixed;

    /**
     * Search for data in memory.
     * 
     * @param string $query The search query
     * @param int $limit Maximum number of results
     * @return array<int, array<string, mixed>> Array of search results
     */
    public function search(string $query, int $limit = 10): array;

    /**
     * Forget/delete data from memory.
     * 
     * @param string $key The memory key
     * @return bool True if data was deleted, false if not found
     */
    public function forget(string $key): bool;

    /**
     * Clear all memory.
     */
    public function clear(): void;

    /**
     * Get memory statistics.
     * 
     * @return array<string, mixed> Memory statistics
     */
    public function getStats(): array;

    /**
     * Check if key exists in memory.
     * 
     * @param string $key The memory key
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool;
}
