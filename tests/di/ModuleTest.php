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
        $this->assertSame($normalize('-array => (array)
-bool => (boolean) 1
-int => (integer) 1
-null => (NULL)
-object => (object) stdClass
-string => (string) 1
Ray\Di\FakeAopInterface- => (dependency) Ray\Di\FakeAop (aop) +returnSame(Ray\Di\FakeDoubleInterceptor)
Ray\Di\FakeDoubleInterceptor- => (dependency) Ray\Di\FakeDoubleInterceptor
Ray\Di\FakeRobotInterface- => (provider) (dependency) Ray\Di\FakeRobotProvider'), $normalize($string));
    }

    /**
     * A closure bound via toInstance() must not break introspection. The old
     * ModuleString deep-copied the whole container with serialize(), which
     * threw "Serialization of 'Closure' is not allowed"; describe() reads the
     * container in place, so an unserializable instance renders as an object.
     *
     * @covers \Ray\Di\ModuleString::__invoke
     */
    public function testToStringWithClosureInstanceDoesNotThrow(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind('')->annotatedWith('callback')->toInstance(static fn (): int => 1);
            }
        };

        $this->assertStringContainsString('-callback => (object) Closure', (string) $module);
    }
}
