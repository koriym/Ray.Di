<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Assisted;

class FakeAssistedNestedConsumer
{
    /** @var MethodInvocationProvider */
    private $invocationProvider;

    /** @var FakeAssistedParamsConsumer */
    private $inner;

    public function __construct(MethodInvocationProvider $invocationProvider, FakeAssistedParamsConsumer $inner)
    {
        $this->invocationProvider = $invocationProvider;
        $this->inner = $inner;
    }

    /**
     * Call another intercepted method, then read the current invocation again
     *
     * @return array{MethodInvocation<object>, MethodInvocation<object>} invocation before and after the inner call
     */
    public function outer($id, #[Assisted] ?FakeAbstractDb $db = null)
    {
        $before = $this->invocationProvider->get();
        $this->inner->getUser($id);
        $after = $this->invocationProvider->get();

        return [$before, $after];
    }
}
