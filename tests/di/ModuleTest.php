<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\NotFound;

use function str_replace;

class ModuleTest extends TestCase
{
    public function testConstructionRegistersBindings(): void
    {
        $module = new FakeInstanceBindModule();
        $this->assertSame(1, $module->getContainer()->getInstance('', 'one'));
    }

    public function testInstall(): void
    {
        $module = new FakeInstallModule();
        // both installed modules' bindings are resolvable with their bound values
        $this->assertSame(1, $module->getContainer()->getInstance('', 'one'));
        $this->assertSame(2, $module->getContainer()->getInstance('', 'two'));
    }

    public function testToInvalidClass(): void
    {
        $this->expectException(NotFound::class);
        new FakeToBindInvalidClassModule();
    }

    public function testRename(): void
    {
        $module = new FakeRenameModule(new FakeToBindModule());
        $instance = $module->getContainer()->getInstance(FakeRobotInterface::class, 'original');
        // assert the concrete class: the renamed binding must be the one the
        // chained module registered, not merely something type-compatible
        $this->assertInstanceOf(FakeRobot::class, $instance);
    }

    public function testModuleWithoutParentConstructorCall(): void
    {
        $module = new FakeNoConstructorCallModule();
        // configure() never ran in the constructor; getContainer() must
        // activate the module lazily so its bindings resolve
        $instance = $module->getContainer()->getInstance(FakeRobotInterface::class, Name::ANY);
        $this->assertInstanceOf(FakeRobot::class, $instance);
    }

    public function testToString(): void
    {
        $string = (string) new FakeLogStringModule();
        $normalize = static function (string $str): string {
            return str_replace(["\r\n", "\r"], "\n", $str);
        };
        $expected = <<<'EOT'
module
├── ''
│   ├── named:array ─ toInstance:(array)
│   ├── named:bool ─ toInstance:true
│   ├── named:false ─ toInstance:false
│   ├── named:float ─ toInstance:1.5
│   ├── named:int ─ toInstance:1
│   ├── named:null ─ toInstance:null
│   ├── named:object ─ toInstance:(stdClass)
│   └── named:string ─ toInstance:'1'
├── Ray\Di\FakeAopInterface
│   └── to:Ray\Di\FakeAop
│       └─intercept─ returnSame: Ray\Di\FakeDoubleInterceptor
├── Ray\Di\FakeDoubleInterceptor
│   └── to:Ray\Di\FakeDoubleInterceptor ─ in:Singleton
└── Ray\Di\FakeRobotInterface
    └── toProvider:Ray\Di\FakeRobotProvider ─ in:Singleton
EOT;
        $this->assertSame($normalize($expected), $normalize($string));
    }

    public function testtoStringWithToNull(): void
    {
        $module = new FakeToNullModule();
        $string = (string) $module;
        $this->assertStringContainsString('└── toNull', $string);
    }

    public function testtoStringWithMultipleAopMethods(): void
    {
        $module = new FakeMultiAopModule();
        $string = (string) $module;
        $this->assertStringContainsString('├─intercept─ methodA', $string);
        $this->assertStringContainsString('└─intercept─ methodB', $string);
    }

    public function testtoStringWithAopOnNonLastBinding(): void
    {
        $module = new FakeMultiBindingAopModule();
        $string = (string) $module;
        // When AOP binding is not last in group, continuation line uses '│   ' prefix
        $this->assertStringContainsString('├── named:first', $string);
        $this->assertStringContainsString('│   └─intercept─', $string);
        $this->assertStringContainsString('└── named:second', $string);
    }

    public function testtoStringWithExplicitSingletonClass(): void
    {
        $module = new FakeSingletonClassModule();
        $string = (string) $module;
        // Explicit class binding with Singleton scope (not via interceptor)
        $this->assertStringContainsString('to:Ray\Di\FakeRobot ─ in:Singleton', $string);
    }
}
