# Ray.Di

## A dependency injection framework for PHP

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Di/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Di/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Di/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Di)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Di/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Di)
[![Continuous Integration](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml)
[![Total Downloads](https://poser.pugx.org/ray/di/downloads)](https://packagist.org/packages/ray/di)

<img src="https://ray-di.github.io/images/logo.svg" width=160  alt="logo">

Ray.Di is DI and AOP framework for PHP inspired by [Google Guice](https://github.com/google/guice/wiki).

## Binding diagnostics

An `Injector` created with an explicit `$tmpDir` writes the composed bindings
to `{$tmpDir}/bindings.md` — a deterministic, human- and agent-readable
snapshot of every resolved binding, the modules that composed them, and the
binding provenance:

```php
new Injector(new AppModule(), $tmpDir); // writes {$tmpDir}/bindings.md
```

The snapshot is written once at composition time, atomically, and only when
the composed bindings changed; `getInstance()` performs no diagnostics I/O.

Install [Ray.ObjectGrapher](https://github.com/ray-di/Ray.ObjectGrapher) to
visualize composed bindings and the object graph:

```bash
composer require --dev ray/object-visual-grapher
```

https://ray-di.github.io
