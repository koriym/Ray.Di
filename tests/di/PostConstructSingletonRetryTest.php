<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function assert;

/**
 * Pins the singleton cache rollback contract for @PostConstruct failures
 *
 * When a singleton's @PostConstruct throws, the half-initialized instance must
 * not stay cached: the next resolution must rebuild from scratch (re-running the
 * constructor and PostConstruct) and return a fully initialized object.
 *
 * Ray.Compiler already unwinds the cache on PostConstruct failure; this test
 * aligns the runtime Injector with that behaviour.
 */
class PostConstructSingletonRetryTest extends TestCase
{
    protected function setUp(): void
    {
        FakePostConstructRetrySingleton::reset();
    }

    public function testSingletonRebuildsAfterPostConstructThrows(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakePostConstructRetrySingleton::class)->in(Scope::SINGLETON);
            }
        });

        // First resolution: PostConstruct throws.
        try {
            $injector->getInstance(FakePostConstructRetrySingleton::class);
            $this->fail('Expected PostConstruct to throw on the first resolution');
        } catch (RuntimeException $e) {
            $this->assertSame('PostConstruct failed on first call', $e->getMessage());
        }

        // Second resolution must rebuild, not return the cached half-initialized instance.
        $instance = $injector->getInstance(FakePostConstructRetrySingleton::class);

        assert($instance instanceof FakePostConstructRetrySingleton);

        // The constructor ran again — the singleton cache was rolled back, not reused.
        $this->assertSame(2, FakePostConstructRetrySingleton::$constructCount);
        // PostConstruct ran again — the second attempt was not short-circuited.
        $this->assertSame(2, FakePostConstructRetrySingleton::$postConstructCount);
        // The returned object is fully initialized.
        $this->assertTrue(FakePostConstructRetrySingleton::$initialized);
        $this->assertTrue($instance::$initialized);
    }
}
