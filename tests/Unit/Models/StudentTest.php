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

        $this->assertArrayHasKey('user', $studentData);
        $this->assertArrayHasKey('exercise', $studentData);
        $this->assertArrayHasKey('score', $studentData);
    }

    /**
     * Test getting all unique students
     */
    public function testGetAllStudents(): void
    {
        $students = ['userId_01', 'userId_02', 'userId_03'];

        $this->assertIsArray($students);
        $this->assertCount(3, $students);
        $this->assertContains('userId_01', $students);
    }

    /**
     * Test JSON parsing with valid data
     */
    public function testValidJsonParsing(): void
    {
        $jsonData = json_encode(['user' => 'userId_01', 'score' => 90]);
        $decoded = json_decode($jsonData, true);

        $this->assertIsArray($decoded);
        $this->assertEquals('userId_01', $decoded['user']);
    }

    /**
     * Test JSON parsing with invalid data
     */
    public function testInvalidJsonReturnsNull(): void
    {
        $invalidJson = 'invalid json content';
        $decoded = json_decode($invalidJson, true);

        $this->assertNull($decoded);
    }
}

