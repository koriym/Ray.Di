<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

/**
 * Thrown when MethodInvocationProvider::get() is called with no invocation set
 *
 * The current MethodInvocation is recorded by the AOP interceptor chain,
 * so it is only available while an intercepted method is executing.
 */
final class MethodInvocationNotAvailable extends Unbound
{
}
