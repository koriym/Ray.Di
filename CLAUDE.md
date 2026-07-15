# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Ray.Di is a dependency injection and AOP (Aspect-Oriented Programming) framework for PHP inspired by Google Guice. It provides annotations-based dependency injection with support for AOP interceptors.

## Core Architecture

### Key Components
- **AbstractModule**: Base class for defining dependency bindings. Modules are composed using `install()` and can be overridden using `override()`
- **Injector**: Main entry point that manages the DI container and creates instances. Auto-registers generated proxy classes and handles untargeted bindings
- **Bind**: Fluent API for creating bindings (`.to()`, `.toProvider()`, `.toInstance()`, `.in()`)
- **Container**: Internal storage for all bindings and dependencies
- **Annotations**: Located in `src/di/Di/` - includes `@Inject`, `@Named`, `@Assisted`, etc.

### Directory Structure
- `src/di/`: Core DI framework code
- `src-deprecated/`: Legacy code maintained for compatibility  
- `tests/di/`: Unit tests with extensive fake classes for testing
- `demo/` and `demo-php8/`: Examples showing framework usage
- Compiled proxy classes are cached in configurable temp directories

## Development Commands

### Testing
```bash
composer test              # Run PHPUnit tests
composer coverage          # Generate test coverage with Xdebug
composer pcov              # Generate coverage with PCOV (faster)
```

### Code Quality
```bash
composer cs                # Run PHP_CodeSniffer
composer cs-fix            # Auto-fix coding standards
composer sa                # Static analysis (Psalm + PHPStan)
composer clean             # Clear analysis caches
```

### Build Pipeline
```bash
composer build             # Full build: cs + sa + pcov + metrics
composer tests             # Quick check: cs + sa + test
```

### Analysis Tools
```bash
composer phpmd             # PHP Mess Detector
composer metrics           # Generate code metrics
composer baseline          # Update static analysis baselines
```

## Testing Strategy

- Tests use extensive fake classes in `tests/di/Fake/` to simulate real-world scenarios
- Supports both PHP 7.2+ and PHP 8+ with separate test suites
- Cache files are automatically cleaned between test runs
- AOP proxy generation is tested with temporary directories

### Behavioral Contract Tests

Coverage measures execution, not meaning: PR #319 inverted module composition
priority while 100% branch coverage stayed green. The semantics are pinned by
contract tests; `tests/di/README.md` maps every contract to its test.

When changing binding registration, merge order, scopes, or AOP weaving:

1. Read `tests/di/ModuleCompositionTest.php` first — it is the composition
   spec ("which binding wins"). If your change flips any outcome there, it is
   a backward-compatibility break: stop and discuss, do not adjust the test.
2. Assert winners, not types: concrete class, `assertSame` identity, or
   invocation order. Asserting only an interface passes regardless of which
   binding won.
3. A new route by which bindings enter the container requires collision tests
   against every existing route (bind / install / constructor chain / override).
4. Behavior changes start with a red contract test in its own commit, then the
   fix that turns it green.

## Framework Patterns

### Module Definition
```php
class MyModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(Interface::class)->to(Implementation::class);
        $this->bind(Service::class)->toProvider(ServiceProvider::class);
    }
}
```

### Injection Usage
```php
$injector = new Injector(new MyModule());
$instance = $injector->getInstance(Interface::class);
```

### Exception Classes

Name the class so it states what went wrong; let the phpdoc explain how
execution reaches it. The name is the summary — never a generic exception
(`LogicException`, `RuntimeException`); create a specific domain exception
under `Ray\Di\Exception` implementing `ExceptionInterface`.

The phpdoc adds only what the class name cannot carry:

- **Summary line (`Thrown when ...`)**: write it only when it says more than
  the name — the precise trigger condition, the call site, or an unfamiliar
  term. Omit it when it would merely restate the name (e.g. `Unbound`,
  `CircularDependency` open directly with `Message format:`).
- **`Message format:` line**: add it when the exception message is just
  variables, so a reader knows what those variables hold.
- **Body**: reserve for context the name cannot express — how the code
  reaches this branch, the design reason, recovery behavior, or a
  "no longer thrown, kept for BC" note.

## Important Notes

- Ray.Di generates proxy classes for AOP which are cached in temp directories
- The framework supports both constructor and setter injection
- All bindings are resolved at runtime with automatic proxy weaving for aspects
- Multi-binding support allows collecting multiple implementations of the same interface