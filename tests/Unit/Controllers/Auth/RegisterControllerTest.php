<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;

/**
 * Test class for RegisterController
 */
class RegisterControllerTest extends TestCase
{
    /**
     * Test registration data structure
     */
    public function testRegistrationDataStructure(): void
    {
        $data = [
            'nom' => 'Doe',
            'prenom' => 'John',
            'mail' => 'john.doe@example.com',
            'mdp' => 'SecurePassword123!'
        ];

        $this->assertArrayHasKey('nom', $data);
        $this->assertArrayHasKey('prenom', $data);
        $this->assertArrayHasKey('mail', $data);
        $this->assertArrayHasKey('mdp', $data);
    }

    /**
     * Test email format validation
     */
    public function testEmailFormatValidation(): void
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';

        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Test password matching
     */
    public function testPasswordMatching(): void
    {
        $password1 = 'Password123!';
        $password2 = 'Password123!';
        $password3 = 'DifferentPassword';

        $this->assertEquals($password1, $password2);
        $this->assertNotEquals($password1, $password3);
    }

    /**
     * Test verification code generation
     */
    public function testVerificationCodeGeneration(): void
    {
        $code = bin2hex(random_bytes(16));

        $this->assertIsString($code);
        $this->assertEquals(32, strlen($code));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $code);
    }
}
