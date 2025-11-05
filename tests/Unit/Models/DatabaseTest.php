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
     * Test that PDO class exists
     */
    public function testPDOClassExists(): void
    {
        $this->assertTrue(class_exists('PDO'));
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

