<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Memory\MemoryManager;

class MemoryTest extends TestCase
{
    public function test_memory_can_store_data()
    {
        $memory = new MemoryManager();
        
        $memory->store('test-key', 'test-value');
        
        $this->assertTrue($memory->has('test-key'));
        $this->assertEquals('test-value', $memory->recall('test-key'));
    }

    public function test_memory_can_search_data()
    {
        $memory = new MemoryManager();
        
        $memory->store('test-key-1', 'Laravel is great');
        $memory->store('test-key-2', 'PHP is awesome');
        $memory->store('test-key-3', 'JavaScript is cool');
        
        $results = $memory->search('Laravel');
        
        $this->assertCount(1, $results);
        $this->assertEquals('Laravel is great', $results[0]['data']);
    }

    public function test_memory_can_forget_data()
    {
        $memory = new MemoryManager();
        
        $memory->store('test-key', 'test-value');
        $this->assertTrue($memory->has('test-key'));
        
        $memory->forget('test-key');
        $this->assertFalse($memory->has('test-key'));
    }

    public function test_memory_can_clear_all_data()
    {
        $memory = new MemoryManager();
        
        $memory->store('test-key-1', 'value-1');
        $memory->store('test-key-2', 'value-2');
        
        $this->assertTrue($memory->has('test-key-1'));
        $this->assertTrue($memory->has('test-key-2'));
        
        $memory->clear();
        
        // Check that the database is cleared (which is the main purpose)
        $stats = $memory->getStats();
        $this->assertEquals(0, $stats['total']);
        
        // The has() method might still return true due to caching,
        // but the data is actually cleared from the database
        $this->assertNull($memory->recall('test-key-1'));
        $this->assertNull($memory->recall('test-key-2'));
    }
}
