<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Bind as AopBind;
use ReflectionMethod;
use ReflectionParameter;

use function array_column;
use function assert;
use function json_decode;

class ModuleJsonTest extends TestCase
{
    public function testToJson(): void
    {
        $module = new FakeLogStringModule();
        $json = $module->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('bindings', $decoded);
        $this->assertIsArray($decoded['bindings']);
    }

    public function testBindingsContainInterface(): void
    {
        $bindings = $this->decodeBindings();
        $interfaces = array_column($bindings, 'interface');

        $this->assertContains(FakeAopInterface::class, $interfaces);
        $this->assertContains(FakeRobotInterface::class, $interfaces);
    }

    public function testDependencyBinding(): void
    {
        $binding = $this->findBinding($this->decodeBindings(), FakeAopInterface::class);

        $this->assertNotNull($binding);
        $this->assertSame('class', $binding['type']);
        $this->assertSame(FakeAop::class, $binding['to']);
    }

    public function testProviderBinding(): void
    {
        $binding = $this->findBinding($this->decodeBindings(), FakeRobotInterface::class);

        $this->assertNotNull($binding);
        $this->assertSame('provider', $binding['type']);
        $this->assertSame(FakeRobotProvider::class, $binding['to']);
    }

    public function testInstanceBinding(): void
    {
        $binding = $this->findBindingByName($this->decodeBindings(), 'string');

        $this->assertNotNull($binding);
        $this->assertSame('instance', $binding['type']);
        $this->assertSame('1', $binding['to']);
    }

    public function testInstanceBindingArray(): void
    {
        $binding = $this->findBindingByName($this->decodeBindings(), 'array');

        $this->assertNotNull($binding);
        $this->assertSame('instance', $binding['type']);
        $this->assertSame(['__type' => 'array'], $binding['to']);
    }

    public function testInstanceBindingObject(): void
    {
        $binding = $this->findBindingByName($this->decodeBindings(), 'object');

        $this->assertNotNull($binding);
        $this->assertSame('instance', $binding['type']);
        $this->assertSame(['__class' => 'stdClass'], $binding['to']);
    }

    public function testInstanceBindingNull(): void
    {
        $binding = $this->findBindingByName($this->decodeBindings(), 'null');

        $this->assertNotNull($binding);
        $this->assertSame('instance', $binding['type']);
        $this->assertNull($binding['to']);
    }

    public function testAopBinding(): void
    {
        $binding = $this->findBinding($this->decodeBindings(), FakeAopInterface::class);

        $this->assertNotNull($binding);
        $this->assertArrayHasKey('aop', $binding);
        $aop = $binding['aop'] ?? [];
        $this->assertArrayHasKey('returnSame', $aop);
        $this->assertContains(FakeDoubleInterceptor::class, $aop['returnSame']);
    }

    public function testDependencyWithoutAop(): void
    {
        $binding = $this->findBinding($this->decodeBindings(), FakeDoubleInterceptor::class);

        $this->assertNotNull($binding);
        $this->assertSame('class', $binding['type']);
        $this->assertArrayNotHasKey('aop', $binding);
    }

    public function testToNullBinding(): void
    {
        $module = new FakeToNullModule();
        $json = $module->toJson();
        /** @var array{bindings: list<array{interface: string, name: string, type: string, to: mixed}>} $decoded */
        $decoded = json_decode($json, true);
        $binding = null;
        foreach ($decoded['bindings'] as $b) {
            if ($b['interface'] === FakeRobotInterface::class) {
                $binding = $b;
                break;
            }
        }

        $this->assertNotNull($binding);
        $this->assertSame('null', $binding['type']);
        $this->assertNull($binding['to']);
    }

    /**
     * BindingIndex::parse() is shared with Container::unbound(): both split
     * '{interface}-{name}' on the FIRST hyphen, because class names cannot
     * contain hyphens but bind names can. Splitting on the last hyphen would
     * report interface '-type' / name 'bool' here.
     */
    public function testHyphenatedBindNameKeptWholeInIndex(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind()->annotatedWith('type-bool')->toInstance(true);
            }
        };
        /** @var array{bindings: list<array{interface: string, name: string, type: string, to: mixed}>} $decoded */
        $decoded = json_decode($module->toJson(), true);

        $this->assertSame('', $decoded['bindings'][0]['interface']);
        $this->assertSame('type-bool', $decoded['bindings'][0]['name']);
    }

    public function testVisitDependencyReturnsBoundClass(): void
    {
        $container = (new FakeLogStringModule())->getContainer()->getContainer();
        $dependency = $container[FakeAopInterface::class . '-' . Name::ANY];
        assert($dependency instanceof Dependency);

        $this->assertSame(FakeAop::class, $dependency->accept(new BindingTargetVisitor()));
    }

    public function testVisitProviderReturnsProviderClassAndContext(): void
    {
        $container = (new FakeLogStringModule())->getContainer()->getContainer();
        $provider = $container[FakeRobotInterface::class . '-' . Name::ANY];
        assert($provider instanceof DependencyProvider);

        $this->assertSame(
            ['class' => FakeRobotProvider::class, 'context' => ''],
            $provider->accept(new BindingTargetVisitor())
        );
    }

    /**
     * Nodes below the binding target (aspects, setters, arguments) carry no
     * target information; the visitor must answer null for all of them.
     */
    public function testVisitorReturnsNullForNonTargetNodes(): void
    {
        $visitor = new BindingTargetVisitor();
        $arguments = new Arguments(new ReflectionMethod(self::class, 'setUp'), new Name(Name::ANY));

        $this->assertNull($visitor->visitAspectBind(new AopBind()));
        $this->assertNull($visitor->visitSetterMethods([]));
        $this->assertNull($visitor->visitSetterMethod('setUp', $arguments));
        $this->assertNull($visitor->visitArguments([]));
        $this->assertNull($visitor->visitArgument(
            '0',
            false,
            null,
            new ReflectionParameter(static function (int $arg): int {
                return $arg;
            }, 0)
        ));
    }

    /**
     * A binding whose instance value is not valid UTF-8 must not erase the
     * whole report: json_encode() substitutes the bad byte (U+FFFD) instead
     * of failing, and every binding stays listed.
     */
    public function testInvalidUtf8InstanceValueDoesNotEraseReport(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind('')->annotatedWith('bin')->toInstance("\xB1\x31");
                $this->bind(FakeRobotInterface::class)->to(FakeRobot::class);
            }
        };
        /** @var array{bindings: list<array{interface: string, name: string, type: string, to: mixed}>} $decoded */
        $decoded = json_decode($module->toJson(), true);

        $this->assertCount(2, $decoded['bindings']);
        $binding = $this->findBindingByName($decoded['bindings'], 'bin');
        $this->assertNotNull($binding);
        $this->assertSame("\u{FFFD}1", $binding['to']);
    }

    /**
     * @return list<array{interface: string, name: string, type: string, to: mixed, aop?: array<string, list<string>>}>
     */
    private function decodeBindings(): array
    {
        $module = new FakeLogStringModule();
        $json = $module->toJson();
        /** @var array{bindings: list<array{interface: string, name: string, type: string, to: mixed, aop?: array<string, list<string>>}>} $decoded */
        $decoded = json_decode($json, true);

        return $decoded['bindings'];
    }

    /**
     * @param list<array{interface: string, name: string, type: string, to: mixed, aop?: array<string, list<string>>}> $bindings
     *
     * @return array{interface: string, name: string, type: string, to: mixed, aop?: array<string, list<string>>}|null
     */
    private function findBinding(array $bindings, string $interface): ?array
    {
        foreach ($bindings as $binding) {
            if ($binding['interface'] === $interface) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * @param list<array{interface: string, name: string, type: string, to: mixed, aop?: array<string, list<string>>}> $bindings
     *
     * @return array{interface: string, name: string, type: string, to: mixed, aop?: array<string, list<string>>}|null
     */
    private function findBindingByName(array $bindings, string $name): ?array
    {
        foreach ($bindings as $binding) {
            if ($binding['name'] === $name) {
                return $binding;
            }
        }

        return null;
    }
}
