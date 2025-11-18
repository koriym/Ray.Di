<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;

class LegacyStringParserTest extends TestCase
{
    public function testParseStringFormat(): void
    {
        $result = LegacyStringParser::parse('engine=engine_name,var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testParseStringFormatWithSpaces(): void
    {
        $result = LegacyStringParser::parse('engine=engine_name, var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testParseStringFormatWithDollarPrefix(): void
    {
        $result = LegacyStringParser::parse('$engine=engine_name,$var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }
}
