<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;

use function error_reporting;
use function restore_error_handler;
use function set_error_handler;

use const E_USER_DEPRECATED;

class LegacyStringParserTest extends TestCase
{
    public function testParseStringFormat(): void
    {
        $result = @LegacyStringParser::parse('engine=engine_name,var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testParseStringFormatWithSpaces(): void
    {
        $result = @LegacyStringParser::parse('engine=engine_name, var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testParseStringFormatWithDollarPrefix(): void
    {
        $result = @LegacyStringParser::parse('$engine=engine_name,$var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testDeprecationWarning(): void
    {
        $errorTriggered = false;
        $errorMessage = '';

        set_error_handler(static function (int $errno, string $errstr) use (&$errorTriggered, &$errorMessage): bool {
            if ($errno === E_USER_DEPRECATED) {
                $errorTriggered = true;
                $errorMessage = $errstr;
            }

            return true;
        });

        LegacyStringParser::parse('engine=engine_name');

        restore_error_handler();

        $this->assertTrue($errorTriggered, 'Deprecation warning was not triggered');
        $this->assertStringContainsString('deprecated', $errorMessage);
        $this->assertStringContainsString('parameter-level attributes', $errorMessage);
    }
}
