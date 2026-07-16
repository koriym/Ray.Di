# Changelog

## 2.22.0 - 2026-07-16

### Added

- Add `Ray\Bindings\ModuleVisitorInterface` and `Ray\Bindings\Bindings` snapshots for rendering composed bindings as Markdown or HTML.

### Changed

- Stop generating `bindings.md` and its signature as a side effect of constructing an `Injector`; collect bindings with `$module->accept(new Bindings())` when diagnostics are needed.
- Move the canonical `BindingsMarkdown` writer to `Ray\Bindings`; the deprecated `Ray\Di\BindingsMarkdown` name remains as a compatibility adapter.

## 2.21.2 - 2026-07-16

### Fixed

- Resolve bindings viewer source links correctly when multiple packages share a PSR-4 prefix by checking the optional Composer vendor directory ([#330](https://github.com/ray-di/Ray.Di/pull/330)).

## 2.21.1 - 2026-07-15

### Changed

- Update `infection/infection` to ^0.34 in development dependencies.

### Deprecated

- Mark `InvalidContext`, `InvalidToConstructorNameParameter`, and the base `Ray\Di\Exception` as deprecated and relocate them to `src-deprecated/`; none has been thrown since earlier releases tightened the related method signatures ([#329](https://github.com/ray-di/Ray.Di/pull/329)).

### Fixed

- Fix assisted injection re-entry with Ray.Aop 2.22's parent-call dispatch, and preserve explicit `null` arguments during assisted injection ([#328](https://github.com/ray-di/Ray.Di/pull/328)).

### Included pull requests

- [#328 Fix assisted injection re-entry](https://github.com/ray-di/Ray.Di/pull/328)
- [#329 Make exception phpdoc consistent and self-documenting](https://github.com/ray-di/Ray.Di/pull/329)

## 2.21.0 - 2026-07-15

### Added

- Add a binding provenance log and emit `bindings.md` during module composition ([#323](https://github.com/ray-di/Ray.Di/pull/323)).
- Add a reusable `Ray\Bindings\BindingsHtml` viewer and the `bin/bindings-html` command ([#325](https://github.com/ray-di/Ray.Di/pull/325)).
- Detect circular dependencies and report them with a dedicated exception, and add the official `AbstractModule::renameBinding()` API ([#319](https://github.com/ray-di/Ray.Di/pull/319)).

### Changed

- Improve module composition compatibility and DI hot-path performance ([#316](https://github.com/ray-di/Ray.Di/pull/316), [#319](https://github.com/ray-di/Ray.Di/pull/319)).
- Update the CI and development toolchain for PHP 8.5, PHPUnit 11, PHP_CodeSniffer 4, and mutation testing ([#315](https://github.com/ray-di/Ray.Di/pull/315), [#317](https://github.com/ray-di/Ray.Di/pull/317), [#326](https://github.com/ray-di/Ray.Di/pull/326)).

### Fixed

- Preserve provider constructor injection points during dependency resolution ([#320](https://github.com/ray-di/Ray.Di/pull/320)).
- Correct JIT binding recursion, singleton handling, multi-binding scope clearing, and multi-interceptor registration ([#318](https://github.com/ray-di/Ray.Di/pull/318)).
- Cache `bindings.md` generation when the binding signature is unchanged ([#327](https://github.com/ray-di/Ray.Di/pull/327)).

### Included pull requests

- [#311 Update license copyright year(s)](https://github.com/ray-di/Ray.Di/pull/311)
- [#315 Add mutation testing with Infection framework](https://github.com/ray-di/Ray.Di/pull/315)
- [#316 Improve DI hot path performance](https://github.com/ray-di/Ray.Di/pull/316)
- [#317 Raise mutation MSI to 90%](https://github.com/ray-di/Ray.Di/pull/317)
- [#318 Fix DI bugs and cleanups](https://github.com/ray-di/Ray.Di/pull/318)
- [#319 Fix core resolution and module composition](https://github.com/ray-di/Ray.Di/pull/319)
- [#320 Preserve provider injection points](https://github.com/ray-di/Ray.Di/pull/320)
- [#323 Add binding provenance logging](https://github.com/ray-di/Ray.Di/pull/323)
- [#325 Add the bindings HTML viewer](https://github.com/ray-di/Ray.Di/pull/325)
- [#326 Bump CI tooling to PHP 8.5/8.4](https://github.com/ray-di/Ray.Di/pull/326)
- [#327 Optimize bindings markdown generation](https://github.com/ray-di/Ray.Di/pull/327)
