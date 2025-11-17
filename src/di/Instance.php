<?php

declare(strict_types=1);

namespace Ray\Di;

use function gettype;
use function is_object;
use function is_scalar;
use function sprintf;

final class Instance implements DependencyInterface, AcceptInterface
{
    /**
     * @param mixed $value
     */
    public function __construct(public $value)
    {
    }

    public function __toString(): string
    {
        if (is_scalar($this->value)) {
            return sprintf(
                '(%s) %s',
                gettype($this->value),
                (string) $this->value
            );
        }

        if (is_object($this->value)) {
            return '(object) ' . $this->value::class;
        }

        return '(' . gettype($this->value) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $index = (string) $bind;
        $container[$index] = $bind->getBound();
    }

    /**
     * {@inheritdoc}
     */
    public function inject(Container $container)
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function setScope($scope): void
    {
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor)
    {
        return $visitor->visitInstance($this->value);
    }
}
