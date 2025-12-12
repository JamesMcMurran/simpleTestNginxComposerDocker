<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testDatabaseConfigurationExists(): void
    {
        // Test that environment variables can be read
        $dbHost = getenv('DB_HOST') ?: 'postgres';
        $this->assertIsString($dbHost);
        $this->assertNotEmpty($dbHost);
    }

    public function testJsonEncodingWorks(): void
    {
        // Test basic JSON functionality used in API
        $data = ['success' => true, 'message' => 'Test'];
        $json = json_encode($data);
        
        $this->assertIsString($json);
        $this->assertStringContainsString('success', $json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }

    public function testPdoClassExists(): void
    {
        // Ensure PDO is available
        $this->assertTrue(class_exists('PDO'));
    }
}
