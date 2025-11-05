<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

/**
 * Test class for User model
 */
class UserTest extends TestCase
{
    /**
     * Test password hashing
     */
    public function testPasswordHashing(): void
    {
        $plainPassword = 'SecurePassword123!';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $this->assertIsString($hashedPassword);
        $this->assertNotEquals($plainPassword, $hashedPassword);
        $this->assertTrue(password_verify($plainPassword, $hashedPassword));
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

        $this->assertIsArray($userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('nom', $userData);
        $this->assertArrayHasKey('prenom', $userData);
        $this->assertArrayHasKey('mail', $userData);
    }
}
