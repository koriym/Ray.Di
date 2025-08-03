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

## Important Notes

- Ray.Di generates proxy classes for AOP which are cached in temp directories
- The framework supports both constructor and setter injection
- All bindings are resolved at runtime with automatic proxy weaving for aspects
- Multi-binding support allows collecting multiple implementations of the same interface