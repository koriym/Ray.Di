# Ray.Di

## A dependency injection framework for PHP

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Di/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Di/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Di/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Di)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Di/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Di)
[![Continuous Integration](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml)
[![Total Downloads](https://poser.pugx.org/ray/di/downloads)](https://packagist.org/packages/ray/di)

<img src="https://ray-di.github.io/images/logo.svg" width=160  alt="logo">

Ray.Di is DI and AOP framework for PHP inspired by [Google Guice](https://github.com/google/guice/wiki).

https://ray-di.github.io

## Binding diagnostics

When the injector is created with a writable directory, Ray.Di records how the container was composed — every binding, its module, and which binding won each collision — and writes `bindings.md` into it. The [ray/bindings](https://github.com/ray-di/Ray.Bindings) package renders it as an interactive HTML page. See [Binding Diagnostics](https://ray-di.github.io/manuals/1.0/en/binding_diagnostics.html) in the manual.

<img src="https://ray-di.github.io/images/bindings.png" alt="bindings.html: object graph, summary, and binding list" width="100%">
