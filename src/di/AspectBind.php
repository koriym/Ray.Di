<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind as AopBind;
use Ray\Aop\MethodInterceptor;

use function assert;

/**
 * @psalm-import-type MethodInterceptorBindings from Types
 */
final class AspectBind implements AcceptInterface
{
    /** @var AopBind */
    private $bind;

    public function __construct(AopBind $bind)
    {
        $this->bind = $bind;
    }

    /**
     * Instantiate interceptors
     *
     * @return MethodInterceptorBindings
     */
    public function inject(Container $container): array
    {
        $bindings = $this->bind->getBindings();
        $instantiatedBindings = [];
        foreach ($bindings as $methodName => $interceptorClassNames) {
            $interceptors = [];
            foreach ($interceptorClassNames as $interceptorClassName) {
                /** @var class-string $interceptorClassName */
                $interceptor = $container->getInstance($interceptorClassName);
                assert($interceptor instanceof MethodInterceptor);
                $interceptors[] = $interceptor;
            }

            $instantiatedBindings[$methodName] = $interceptors;
        }

        return $instantiatedBindings;
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor)
    {
        $visitor->visitAspectBind($this->bind);
    }
}
