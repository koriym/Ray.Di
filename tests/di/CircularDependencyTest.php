<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\CircularDependency;

class CircularDependencyTest extends TestCase
{
    public function testMutualCircularDependencyIsDetected(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeCircularAInterface::class)->to(FakeCircularA::class);
                $this->bind(FakeCircularBInterface::class)->to(FakeCircularB::class);
            }
        });

        $this->expectException(CircularDependency::class);
        $injector->getInstance(FakeCircularAInterface::class);
    }

    public function testCircularDependencyMessageContainsResolutionPath(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeCircularAInterface::class)->to(FakeCircularA::class);
                $this->bind(FakeCircularBInterface::class)->to(FakeCircularB::class);
            }
        });

        $this->expectException(CircularDependency::class);
        $this->expectExceptionMessage(FakeCircularAInterface::class . '- -> ' . FakeCircularBInterface::class . '- -> ' . FakeCircularAInterface::class . '-');
        $injector->getInstance(FakeCircularAInterface::class);
    }

    public function testSingletonCircularDependencyIsDetected(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeCircularAInterface::class)->to(FakeCircularA::class)->in(Scope::SINGLETON);
                $this->bind(FakeCircularBInterface::class)->to(FakeCircularB::class)->in(Scope::SINGLETON);
            }
        });

        $this->expectException(CircularDependency::class);
        $injector->getInstance(FakeCircularAInterface::class);
    }

    public function testSelfCircularDependencyIsDetected(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeSelfCircularInterface::class)->to(FakeSelfCircular::class);
            }
        });

        $this->expectException(CircularDependency::class);
        $injector->getInstance(FakeSelfCircularInterface::class);
    }

    public function testDiamondDependencyIsNotFalselyDetected(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeDiamondSharedInterface::class)->to(FakeDiamondShared::class);
            }
        });

        $root = $injector->getInstance(FakeDiamondRoot::class);

        $this->assertInstanceOf(FakeDiamondShared::class, $root->left);
        $this->assertInstanceOf(FakeDiamondShared::class, $root->right);
    }

    public function testResolutionSucceedsAfterCircularDependencyIsCaught(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeCircularAInterface::class)->to(FakeCircularA::class);
                $this->bind(FakeCircularBInterface::class)->to(FakeCircularB::class);
                $this->bind(FakeDiamondSharedInterface::class)->to(FakeDiamondShared::class);
            }
        });

        try {
            $injector->getInstance(FakeCircularAInterface::class);
        } catch (CircularDependency) {
        }

        // Resolution state is cleaned up: an unrelated binding resolves normally afterwards
        $this->assertInstanceOf(FakeDiamondShared::class, $injector->getInstance(FakeDiamondSharedInterface::class));
    }
}
