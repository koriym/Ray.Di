<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Coroutine id resolution without a coroutine extension
 *
 * The resolver selection (swoole / openswoole / fallback) is exercised for
 * every extension combination. The openswoole closure body is excluded from
 * coverage because ext-swoole and ext-openswoole cannot be loaded in the same
 * process, so it is unreachable from any single test environment.
 */
class CoroutineContextTest extends TestCase
{
    public function testFallbackResolverReturnsZero(): void
    {
        $resolver = $this->detectIdResolver(false, false);
        $this->assertSame(0, $resolver());
    }

    #[RequiresPhpExtension('swoole')]
    public function testSwooleResolverUsesSwoole(): void
    {
        $resolver = $this->detectIdResolver(true, false);
        $this->assertSame(0, $resolver());
    }

    #[RequiresPhpExtension('openswoole')]
    public function testOpenswooleResolverIsSelected(): void
    {
        $resolver = $this->detectIdResolver(false, true);
        $this->assertIsCallable($resolver);
    }

    public function testIdWithoutExtensionIsZero(): void
    {
        $this->assertSame(0, CoroutineContext::id());
    }

    /** @return callable(): int */
    private function detectIdResolver(bool $swoole, bool $openswoole): callable
    {
        $method = new ReflectionMethod(CoroutineContext::class, 'detectIdResolver');
        $method->setAccessible(true);

        /** @var callable(): int */
        return $method->invoke(null, $swoole, $openswoole);
    }
}
