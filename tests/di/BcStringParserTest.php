<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;

class BcStringParserTest extends TestCase
{
    public function testParseStringFormat(): void
    {
        $result = BcStringParser::parse('engine=engine_name,var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testParseStringFormatWithSpaces(): void
    {
        $result = BcStringParser::parse('engine=engine_name, var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }

    public function testParseStringFormatWithDollarPrefix(): void
    {
        $result = BcStringParser::parse('$engine=engine_name,$var=var_name');
        $expected = ['engine' => 'engine_name', 'var' => 'var_name'];
        $this->assertSame($expected, $result);
    }
}
