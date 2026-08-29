<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Assisted;
use Swoole\Coroutine;

/**
 * Intercepted consumer that suspends mid-call, so another coroutine's
 * interception begins before this one reads its invocation
 */
class FakeAssistedCoroutineConsumer
{
    /** @var MethodInvocationProvider */
    private $invocationProvider;

    public function __construct(MethodInvocationProvider $invocationProvider)
    {
        $this->invocationProvider = $invocationProvider;
    }

    /** @return MethodInvocation<object> */
    public function currentInvocation(int $marker, #[Assisted] ?FakeAbstractDb $db = null): MethodInvocation
    {
        if (Coroutine::getCid() > 0) {
            Coroutine::sleep(0.05);
        }

        return $this->invocationProvider->get();
    }
}
