<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use PHPUnit\Framework\TestCase;
use Ray\Di\FakeEngine;
use Ray\Di\FakeEngine2;
use Ray\Di\FakeEngineInterface;
use Ray\Di\FakeRobot;
use Ray\Di\FakeRobotInterface;
use Ray\Di\MultiBinder;
use Ray\Di\NullModule;

class MultiBinderTest extends TestCase
{
    public function testAdd(): void
    {
        $module = new NullModule();
        $binder = MultiBinder::newInstance($module, FakeEngineInterface::class);
        $binder->addBinding('one')->to(FakeEngine::class);
        $binder->addBinding('two')->to(FakeEngine2::class);
        /** @var MultiBindings $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        $this->assertArrayHasKey('one', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('two', (array) $multiBindings[FakeEngineInterface::class]);
    }

    public function testSet(): void
    {
        $module = new NullModule();
        $binder = MultiBinder::newInstance($module, FakeEngineInterface::class);
        $binder->addBinding('one')->to(FakeEngine::class);
        $binder->addBinding('two')->to(FakeEngine2::class);
        $binder->setBinding('one')->to(FakeEngine::class);
        /** @var MultiBindings $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        $this->assertArrayHasKey('one', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayNotHasKey('two', (array) $multiBindings[FakeEngineInterface::class]);
    }

    /**
     * setBinding() must clear only its OWN interface's bindings, not every
     * interface registered in the shared MultiBindings. Binding interface A,
     * then calling setBinding() on a different interface B, must leave A's
     * bindings intact.
     */
    public function testSetBindingDoesNotClearOtherInterfaces(): void
    {
        $module = new NullModule();
        MultiBinder::newInstance($module, FakeEngineInterface::class)->addBinding('one')->to(FakeEngine::class);
        // a different interface replaces its own bindings via setBinding()
        MultiBinder::newInstance($module, FakeRobotInterface::class)->setBinding('robot')->to(FakeRobot::class);

        /** @var MultiBindings $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        // interface A's binding survived the unrelated setBinding() call
        $this->assertArrayHasKey('one', (array) $multiBindings[FakeEngineInterface::class]);
        // interface B's own binding is present
        $this->assertArrayHasKey('robot', (array) $multiBindings[FakeRobotInterface::class]);
    }

    /**
     * newInstance() declares the set, so "declared with no member" is a state
     * the store can represent. Without it a consumer cannot tell an empty set
     * from an interface nobody bound.
     */
    public function testNewInstanceDeclaresEmptySet(): void
    {
        $module = new NullModule();
        MultiBinder::newInstance($module, FakeEngineInterface::class);

        /** @var MultiBindings $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        $this->assertTrue($multiBindings->offsetExists(FakeEngineInterface::class));
        $this->assertSame([], $multiBindings[FakeEngineInterface::class]);
    }

    /**
     * Declaring a set that already has members must leave them alone. Two
     * modules binding the same interface each construct their own MultiBinder,
     * so the second declaration must append rather than wipe the first.
     */
    public function testNewInstanceKeepsExistingMembers(): void
    {
        $module = new NullModule();
        MultiBinder::newInstance($module, FakeEngineInterface::class)->addBinding('one')->to(FakeEngine::class);
        MultiBinder::newInstance($module, FakeEngineInterface::class)->addBinding('two')->to(FakeEngine2::class);

        /** @var MultiBindings $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        $this->assertArrayHasKey('one', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('two', (array) $multiBindings[FakeEngineInterface::class]);
    }
}
