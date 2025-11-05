<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

/**
 * Test class for Database model
 */
class DatabaseTest extends TestCase
{
    /**
     * Test PDO class availability
     */
    public function testPDOClassExists(): void
    {
        $this->assertTrue(class_exists('PDO'));
    }

    /**
     * Test DSN string format
     */
    public function testDSNStringFormat(): void
    {
        $host = 'localhost';
        $dbname = 'testdb';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

        $this->assertIsString($dsn);
        $this->assertStringContainsString('mysql:', $dsn);
        $this->assertStringContainsString('host=', $dsn);
        $this->assertStringContainsString('dbname=', $dsn);
        $this->assertStringContainsString('charset=', $dsn);
    }

    /**
     * Test PDO constants
     */
    public function testPDOConstants(): void
    {
        $this->assertTrue(defined('PDO::ATTR_ERRMODE'));
        $this->assertTrue(defined('PDO::ERRMODE_EXCEPTION'));
        $this->assertEquals(2, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Test connection options structure
     */
    public function testConnectionOptionsStructure(): void
    {
        $options = [
            'host' => 'localhost',
            'port' => 3306,
            'charset' => 'utf8mb4',
            'persistent' => false
        ];

        $this->assertIsArray($options);
        $this->assertArrayHasKey('host', $options);
        $this->assertArrayHasKey('charset', $options);
        $this->assertEquals('utf8mb4', $options['charset']);
    }
}
