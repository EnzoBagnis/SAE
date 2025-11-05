#!/usr/bin/env php
<?php

/**
 * Script de génération automatique de tests unitaires
 * Usage: php generate-tests.php [model_name]
 * Example: php generate-tests.php User
 */

if ($argc < 2) {
    echo "Usage: php generate-tests.php [ModelName]\n";
    echo "Example: php generate-tests.php User\n";
    exit(1);
}

$modelName = $argv[1];
$modelsDir = __DIR__ . '/models';
$testsDir = __DIR__ . '/tests/Unit/Models';
$modelFile = $modelsDir . '/' . $modelName . '.php';

if (!file_exists($modelFile)) {
    echo "Error: Model file not found: $modelFile\n";
    exit(1);
}

// Create tests directory if it doesn't exist
if (!is_dir($testsDir)) {
    mkdir($testsDir, 0755, true);
}

// Parse the model file to extract methods
$content = file_get_contents($modelFile);

// Extract class name
preg_match('/class\s+(\w+)/', $content, $classMatch);
$className = $classMatch[1] ?? $modelName;

// Extract public methods
preg_match_all('/public\s+function\s+(\w+)\s*\((.*?)\)/', $content, $methodMatches);
$methods = $methodMatches[1] ?? [];
$methodParams = $methodMatches[2] ?? [];

// Generate test file
$testContent = generateTestFile($className, $methods, $methodParams);

$testFile = $testsDir . '/' . $className . 'Test.php';
file_put_contents($testFile, $testContent);

echo "✅ Test file generated: $testFile\n";
echo "📝 Generated " . count($methods) . " test methods\n";

function generateTestFile($className, $methods, $methodParams)
{
    $testMethods = '';

    foreach ($methods as $index => $method) {
        if ($method === '__construct') {
            continue;
        }

        $testName = 'test' . ucfirst($method);
        $params = $methodParams[$index];

        $testMethods .= "    /**\n";
        $testMethods .= "     * Test method: {$method}()\n";
        $testMethods .= "     */\n";
        $testMethods .= "    public function {$testName}(): void\n";
        $testMethods .= "    {\n";
        $testMethods .= "        \$this->markTestIncomplete(\n";
        $testMethods .= "            'This test has not been implemented yet. Please implement test for {$method}().'\n";
        $testMethods .= "        );\n";
        $testMethods .= "        \n";
        $testMethods .= "        // TODO: Implement test for {$method}({$params})\n";
        $testMethods .= "        // Example:\n";
        $testMethods .= "        // \$model = new {$className}();\n";
        $testMethods .= "        // \$result = \$model->{$method}(/* parameters */);\n";
        $testMethods .= "        // \$this->assertNotNull(\$result);\n";
        $testMethods .= "    }\n\n";
    }

    return <<<PHP
<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

/**
 * Test class for {$className} model
 * Auto-generated test file
 */
class {$className}Test extends TestCase
{
    /**
     * Setup method - runs before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        // TODO: Add any setup code here (e.g., mock database connection)
    }

    /**
     * Teardown method - runs after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // TODO: Add any cleanup code here
    }

{$testMethods}}

PHP;
}

