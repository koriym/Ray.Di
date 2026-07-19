# Changelog

## 2.22.2 - 2026-07-19

### Fixed

- Reject non-instantiable classes (for example private constructors) from just-in-time binding. They now fail with `Unbound` instead of a bare PHP `Error` after language-level `new` in `NewInstance` ([#335](https://github.com/ray-di/Ray.Di/pull/335)).

## 2.22.1 - 2026-07-17

### Added

- Detect circular dependencies and report them with a dedicated exception, instead of recursing until the process dies ([#319](https://github.com/ray-di/Ray.Di/pull/319)).
- Add a binding provenance log recording how each binding was composed, and `Ray\Bindings\Bindings` snapshots for rendering it as Markdown or HTML — collected explicitly with `$module->accept(new Bindings())` ([#323](https://github.com/ray-di/Ray.Di/pull/323), [#331](https://github.com/ray-di/Ray.Di/pull/331)).
- Add the `Ray\Bindings\BindingsHtml` viewer and the `bin/bindings-html` command ([#325](https://github.com/ray-di/Ray.Di/pull/325), [#330](https://github.com/ray-di/Ray.Di/pull/330)).

### Changed

- Improve module composition compatibility and DI hot-path performance ([#316](https://github.com/ray-di/Ray.Di/pull/316), [#319](https://github.com/ray-di/Ray.Di/pull/319), [#333](https://github.com/ray-di/Ray.Di/pull/333)).
- Update the CI and development toolchain for PHP 8.5, PHPUnit 11, PHP_CodeSniffer 4, and mutation testing ([#315](https://github.com/ray-di/Ray.Di/pull/315), [#317](https://github.com/ray-di/Ray.Di/pull/317), [#326](https://github.com/ray-di/Ray.Di/pull/326)).

### Deprecated

- Mark `InvalidContext`, `InvalidToConstructorNameParameter`, and the base `Ray\Di\Exception` as deprecated and relocate them to `src-deprecated/`; none has been thrown since earlier releases tightened the related method signatures ([#329](https://github.com/ray-di/Ray.Di/pull/329)).

### Fixed

- Preserve provider constructor injection points during dependency resolution ([#320](https://github.com/ray-di/Ray.Di/pull/320)).
- Correct JIT binding recursion, singleton handling, multi-binding scope clearing, and multi-interceptor registration ([#318](https://github.com/ray-di/Ray.Di/pull/318), [#319](https://github.com/ray-di/Ray.Di/pull/319)).
- Fix assisted injection re-entry with Ray.Aop 2.22's parent-call dispatch, and preserve explicit `null` arguments during assisted injection ([#328](https://github.com/ray-di/Ray.Di/pull/328)).
- Reject a rename onto an index that already has a binding instead of silently destroying it ([#319](https://github.com/ray-di/Ray.Di/pull/319)).

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
- [#328 Fix assisted injection re-entry](https://github.com/ray-di/Ray.Di/pull/328)
- [#329 Make exception phpdoc consistent and self-documenting](https://github.com/ray-di/Ray.Di/pull/329)
- [#330 Disambiguate shared PSR-4 source links](https://github.com/ray-di/Ray.Di/pull/330)
- [#331 Add an explicit bindings snapshot API](https://github.com/ray-di/Ray.Di/pull/331)
- [#333 Restore rename() to its wrapper transformation](https://github.com/ray-di/Ray.Di/pull/333)
