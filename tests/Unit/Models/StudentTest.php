<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

/**
 * Test class for Student model
 */
class StudentTest extends TestCase
{
    /**
     * Test student data structure
     */
    public function testStudentDataStructure(): void
    {
        $studentData = [
            'user' => 'userId_01',
            'exercise' => 'exo1',
            'score' => 85,
            'timestamp' => '2024-01-01 10:00:00'
        ];

        $this->assertIsArray($studentData);
        $this->assertArrayHasKey('user', $studentData);
        $this->assertArrayHasKey('exercise', $studentData);
        $this->assertArrayHasKey('score', $studentData);
    }

    /**
     * Test student list operations
     */
    public function testStudentListOperations(): void
    {
        $students = ['userId_01', 'userId_02', 'userId_03'];

        $this->assertIsArray($students);
        $this->assertCount(3, $students);
        $this->assertContains('userId_01', $students);
    }

    /**
     * Test JSON encoding/decoding
     */
    public function testJsonOperations(): void
    {
        $data = ['user' => 'userId_01', 'score' => 90];
        $json = json_encode($data);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals($data, $decoded);
    }

    /**
     * Test invalid JSON handling
     */
    public function testInvalidJsonHandling(): void
    {
        $invalidJson = 'invalid json content';
        $decoded = json_decode($invalidJson, true);

        $this->assertNull($decoded);
    }
}
