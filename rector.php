<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bootstrap/Composition',
        // Every module, named as the parent. Rector does expand globs, but a
        // glob would still need a second entry for tests, and this way a module
        // added tomorrow is refactored without anyone remembering this file.
        __DIR__ . '/app-modules',
        __DIR__ . '/config',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
        // The plugin's own PHP. A path package rather than a module, so the
        // entry above does not reach it — and `phpunit.xml` holds `bridge/src`
        // to the same coverage floor as everything else. `R4` compares the two
        // lists so a tree cannot be in one and not the other again.
        __DIR__ . '/bridge',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    )
    // An architecture rule names namespaces, not classes. Several of the
    // strings it matches on are not class names at all — `Modules\Health\Internal`
    // is a namespace with no class of that name — so rewriting them to ::class
    // would turn a working rule into a reference to something that does not
    // exist.
    // A Pest dataset entry is bound to the test case, and a first-class
    // callable of a *static* method is a static closure — which cannot be
    // bound. So the rewrite is not a tidier spelling of the same thing here;
    // it is `Cannot bind an instance to a static closure`, raised at run time
    // on every test that takes the dataset.
    //
    // Scoped to the contract suite, which is where a dataset names one fake and
    // one adapter. Everywhere else the rule is right and stays on.
    //
    // A screen's state is `protected` because the framework writes it, and the
    // framework is the parent class: `NativeComponent::__syncProperty()`
    // assigns `$this->{$name}` from its own scope, which reaches a protected
    // member of a subclass and does not reach a private one. Privatising them
    // does not fail to compile and does not fail the analyser — it creates a
    // dynamic property instead, so `native:model` silently stops binding and
    // the screen renders with the field it opened with.
    //
    // Measured rather than assumed: with these four made private, eight of the
    // nine tests in `PairingAStackByScanningTest` fail. Scoped to the screens,
    // because everywhere else in a final class the rule is right and stays on.
    ->withSkip([
        // Published by `native:install` rather than written here, and ignored by
        // git — absent in CI and present on every machine that has run a build.
        // Refactoring it would rewrite a file the next install overwrites, and
        // leaving it in makes this gate red locally and green on CI.
        __DIR__ . '/config/nativephp.php',
        StringClassNameToClassConstantRector::class => [__DIR__ . '/tests/Arch'],
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [__DIR__ . '/tests/Contract'],
        // Globbed rather than named, because `household` is a surface too and a
        // path written out here covers the module that exists today. Rector
        // does not expand a glob in a skip path — it compares strings — so the
        // expansion happens here, where a surface added tomorrow is covered
        // without anybody remembering this file.
        PrivatizeFinalClassPropertyRector::class => (array) glob(__DIR__ . '/app-modules/*/src/Internal/Screens'),
    ])
    ->withImportNames(importShortClasses: false)
    ->withCache(__DIR__ . '/.rector-cache');
