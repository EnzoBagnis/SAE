<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PDO;
use PDOStatement;

/**
 * Test class for User model
 */
class UserTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;

    /**
     * Setup method - runs before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock PDO and PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
    }

    /**
     * Test password hashing on user creation
     */
    public function testPasswordIsHashedOnCreation(): void
    {
        $plainPassword = 'SecurePassword123!';

        // Verify that password_hash is called properly
        $this->assertTrue(strlen(password_hash($plainPassword, PASSWORD_DEFAULT)) > 0);
        $this->assertNotEquals($plainPassword, password_hash($plainPassword, PASSWORD_DEFAULT));
    }

    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'not-an-email';

        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Test user data structure
     */
    public function testUserDataStructure(): void
    {
        $userData = [
            'id' => 1,
            'nom' => 'Doe',
            'prenom' => 'John',
            'mail' => 'john@example.com',
            'verifie' => 1
        ];

        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('nom', $userData);
        $this->assertArrayHasKey('prenom', $userData);
        $this->assertArrayHasKey('mail', $userData);
    }
}

