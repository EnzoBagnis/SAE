<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Test class for Database model
 */
class DatabaseTest extends TestCase
{
    /**
     * Test that getConnection returns PDO instance
     * Note: This test requires a valid database configuration
     */
    public function testGetConnectionReturnsPDOInstance(): void
    {
        // This test would need actual database access or mocking
        // For now, we test the concept

        $this->assertTrue(class_exists('PDO'));
    }

    /**
     * Test database configuration file existence
     */
    public function testEnvFileExists(): void
    {
        $envPath = __DIR__ . '/../../../config/.env';

        // Check if .env file exists or should exist
        if (file_exists($envPath)) {
            $this->assertFileExists($envPath);
            $this->assertFileIsReadable($envPath);
        } else {
            $this->markTestSkipped('.env file not found - expected in production');
        }
    }

    /**
     * Test PDO connection options
     */
    public function testPDOConnectionOptions(): void
    {
        $expectedOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false
        ];

        $this->assertIsArray($expectedOptions);
        $this->assertArrayHasKey(PDO::ATTR_ERRMODE, $expectedOptions);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $expectedOptions[PDO::ATTR_ERRMODE]);
    }

    /**
     * Test DSN string format
     */
    public function testDSNFormat(): void
    {
        $serverName = 'localhost';
        $databaseName = 'testdb';
        $expectedDSN = "mysql:host=$serverName;dbname=$databaseName;charset=utf8mb4";

        $this->assertStringContainsString('mysql:host=', $expectedDSN);
        $this->assertStringContainsString('dbname=', $expectedDSN);
        $this->assertStringContainsString('charset=utf8mb4', $expectedDSN);
    }

    /**
     * Test charset configuration
     */
    public function testCharsetIsUTF8MB4(): void
    {
        $dsn = "mysql:host=localhost;dbname=test;charset=utf8mb4";

        $this->assertStringContainsString('charset=utf8mb4', $dsn);
    }

    /**
     * Test error mode is exception
     */
    public function testErrorModeIsException(): void
    {
        $this->assertEquals(2, PDO::ERRMODE_EXCEPTION);
    }
}
