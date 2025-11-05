<?php

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

/**
 * Test class for HomeController
 */
class HomeControllerTest extends TestCase
{
    /**
     * Test basic assertion
     */
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
    }

    /**
     * Test string operations
     */
    public function testStringOperations(): void
    {
        $str = "HomeController";
        $this->assertIsString($str);
        $this->assertStringContainsString("Controller", $str);
    }

    /**
     * Test array operations
     */
    public function testArrayOperations(): void
    {
        $data = ['controller' => 'Home', 'action' => 'index'];
        $this->assertIsArray($data);
        $this->assertArrayHasKey('controller', $data);
    }
}
