<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\Pointcut;
use Ray\Di\Di\Assisted;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;
use Ray\Di\Di\PostConstruct;
use Ray\Di\Di\Set;
use Ray\Di\Exception\InvalidProvider;
use Ray\Di\Exception\InvalidType;
use Ray\Di\Exception\NotFound;
use Ray\Di\Exception\Unbound;
use Ray\Di\MultiBinding\LazyInstance;
use Ray\Di\MultiBinding\LazyInterface;
use Ray\Di\MultiBinding\LazyProvider;
use Ray\Di\MultiBinding\LazyTo;

/**
 * Ray.Di Domain Types for Psalm
 *
 * This file contains Psalm type definitions for the Ray.Di dependency injection framework.
 * These types enhance static analysis and provide better IDE support.
 *
 * @psalm-type DependencyContainer = array<non-empty-string, DependencyInterface>
 * @psalm-type DependencyIndex = non-empty-string
 * @psalm-type PointcutList array<int, Pointcut>
 * @psalm-type BindingName = string
 * @psalm-type BindableInterface = class-string|non-empty-string
 * @psalm-type ConstructorNameMapping = array<string, string>
 * @psalm-type ParameterNameMapping = array<string, string>
 * @psalm-type NamedParameterString = string
 * @psalm-type InjectionPointDefinition = array{0: string, 1: string, 2: bool}
 * @psalm-type InjectionPointsList = list<InjectionPointDefinition>
 * @psalm-type MethodArguments = array<int, mixed>
 * @psalm-type ArgumentSerializationData = array{0: string, 1: bool, 2: mixed, 3: string, 4: array{0: string, 1: string, 2: string}}
 * @psalm-type UnboundTypeList = list<'bool'|'int'|'float'|'string'|'array'|'resource'|'callable'|'iterable'|'object'|'mixed'>
 * @psalm-type ScopeType = 'Singleton'|'Prototype'
 * @psalm-type ProviderContext = string
 * @psalm-type MultiBindingMap array<string, non-empty-array<array-key, LazyInterface>>
 * @psalm-type LazyBindingList non-empty-array<array-key, LazyInterface>
 * @psalm-type MethodInterceptorBindings array<non-empty-string, list<MethodInterceptor>>
 * @psalm-type VisitorResult = mixed
 * @psalm-type SetterMethodList = array<SetterMethod>
 * @psalm-type ArgumentList = array<Argument>
 * @psalm-type ReflectionMethodReference = array{0: string, 1: string, 2: string}
 * @psalm-type DependencyMeta = string
 * @psalm-type QualifierList = array<object>
 * @psalm-type ModuleInstallChain = AbstractModule|null
 * @psalm-type ModuleOverrideChain = AbstractModule|null
 * @psalm-type DiException Unbound|Untargeted|NotFound|InvalidProvider|InvalidType
 * @psalm-type BindingErrorReason = 'interface_not_found'|'invalid_provider'|'circular_dependency'|'missing_constructor'|'invalid_scope'
 * @psalm-type SerializableDependencyData = array{newInstance: string, postConstruct: string|null, isSingleton: bool}
 * @psalm-type InjectionPointSerialData = array{0: string, 1: string, 2: string}
 * @psalm-type AnnotationType Named|Inject|Qualifier|PostConstruct|Assisted|Set
 * @psalm-type DependencyImplementation Dependency|DependencyProvider|Instance|NullDependency|NullObjectDependency|LazyInstance|LazyProvider|LazyTo
 * @template TInterceptor of MethodInterceptor
 * @psalm-type InterceptorClassList = array<class-string<TInterceptor>>
 * @template TProvider
 * @psalm-type GenericProvider = ProviderInterface<TProvider>
 * @template TClass of object
 * @psalm-type TypedClassString = class-string<TClass>
 * @template TOptional
 * @psalm-type OptionalBinding = TOptional|null
 */
