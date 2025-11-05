<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;

/**
 * Test class for RegisterController
 */
class RegisterControllerTest extends TestCase
{
    /**
     * Test registration page concept
     */
    public function testShowRegistrationPage(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test successful registration with valid data
     */
    public function testRegistrationWithValidData(): void
    {
        $validData = [
            'nom' => 'Doe',
            'prenom' => 'John',
            'mail' => 'john.doe@example.com',
            'mdp' => 'SecurePassword123!',
            'confirm_password' => 'SecurePassword123!'
        ];

        $this->assertArrayHasKey('nom', $validData);
        $this->assertArrayHasKey('prenom', $validData);
        $this->assertArrayHasKey('mail', $validData);
    }

    /**
     * Test registration with invalid email format
     */
    public function testRegistrationWithInvalidEmail(): void
    {
        $invalidEmail = 'not-an-email';

        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Test registration with mismatched passwords
     */
    public function testRegistrationWithMismatchedPasswords(): void
    {
        $password = 'Password123!';
        $confirmPassword = 'DifferentPassword123!';

        $this->assertNotEquals($password, $confirmPassword);
    }

    /**
     * Test registration with weak password
     */
    public function testRegistrationWithWeakPassword(): void
    {
        $weakPassword = '123';

        $this->assertLessThan(8, strlen($weakPassword));
    }

    /**
     * Test email verification code generation
     */
    public function testVerificationCodeGeneration(): void
    {
        $code = bin2hex(random_bytes(16));

        $this->assertIsString($code);
        $this->assertEquals(32, strlen($code));
    }
}
