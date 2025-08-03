<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * @psalm-import-type DependencyContainer from Types
 * @psalm-import-type ScopeType from Types
 * @psalm-import-type InjectableValue from Types
 */
interface DependencyInterface
{
    /**
     * @return string
     */
    public function __toString();

    /**
     * Inject dependencies into dependent objects
     *
     * @return mixed
     */
    public function inject(Container $container);

    /**
     * Register dependency to container
     *
     * @param DependencyContainer $container
     *
     * @return void
     *
     * @param-out DependencyContainer $container
     */
    public function register(array &$container, Bind $bind);

    /**
     * Set scope
     *
     * @param string $scope
     *
     * @return void
     */
    public function setScope($scope);
}
