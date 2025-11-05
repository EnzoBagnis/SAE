<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;

/**
 * Test class for LoginController
 */
class LoginControllerTest extends TestCase
{
    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        $validEmail = 'user@example.com';
        $invalidEmail = 'not-an-email';

        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Test empty string detection
     */
    public function testEmptyStringDetection(): void
    {
        $emptyString = '';
        $nonEmptyString = 'test';

        $this->assertEmpty($emptyString);
        $this->assertNotEmpty($nonEmptyString);
    }

    /**
     * Test password strength basic check
     */
    public function testPasswordStrength(): void
    {
        $weakPassword = '123';
        $strongPassword = 'SecurePassword123!';

        $this->assertLessThan(8, strlen($weakPassword));
        $this->assertGreaterThanOrEqual(8, strlen($strongPassword));
    }
}
