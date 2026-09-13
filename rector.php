<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;

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
    ->withSkip([
        StringClassNameToClassConstantRector::class => [__DIR__ . '/tests/Arch'],
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [__DIR__ . '/tests/Contract'],
    ])
    ->withImportNames(importShortClasses: false)
    ->withCache(__DIR__ . '/.rector-cache');
