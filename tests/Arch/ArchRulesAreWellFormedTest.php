<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Tests\Support\Tree;

// R3 — an architecture expectation says what it looks like it says.
//
// Pest resolves the strings in an expectation against the autoloader and joins
// several of them in ways that are not visible at the call site. Three shapes
// read as rules and check nothing:
//
//   expect(['A', 'B'])->not->toBeUsedIn(X)   reports only code using BOTH.
//   expect(['app', 'Illuminate\Support'])    reports nothing at all, because a
//                                            function name and a namespace in
//                                            one list cancel out.
//   expect('Modules')                        matches no files: `Modules\Health\`
//                                            is a registered PSR-4 prefix and
//                                            its parent is not.
//
// All three pass. The cost of getting one wrong is a rule that is documented,
// runs on every commit, and permits exactly what it names — so the shapes are
// refused here rather than left to be noticed.
//
// So each of the three says whether it read an expectation at all first. The
// reading below walks a directory and parses every file in it, and a parse that
// fails is stepped over rather than reported — so a syntax the parser does not
// yet know, or a directory renamed, leaves all three judging an empty list and
// saying nothing about any rule in the suite.

/**
 * Every statement in the architecture tests, as the chain of method names it
 * calls and the strings it names.
 *
 * @return list<array{file: string, line: int, methods: list<string>, expected: list<string>, plural: bool}>
 */
function archExpectations(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('tests/Arch'), '.php') as $file) {
        $source = file_get_contents($file);

        if (! is_string($source)) {
            continue;
        }

        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse($source);

        if ($statements === null) {
            continue;
        }

        foreach (expectCalls($statements) as $call) {
            $found[] = [
                'file' => str_replace(sprintf('%s/', Tree::root()), '', $file),
                'line' => $call->getStartLine(),
                'methods' => chainAround($statements, $call),
                'expected' => stringsIn($call->getArgs()[0]->value ?? null),
                'plural' => (($call->getArgs()[0] ?? null)?->value instanceof Array_)
                    && count(stringsIn($call->getArgs()[0]->value)) > 1,
            ];
        }
    }

    return $found;
}

/**
 * Every `expect(...)` in the file, written as a function or chained onto
 * `arch(...)`.
 *
 * Both shapes appear in this suite and they are different node types, so a
 * finder looking for only one of them reads half the rules and reports on the
 * other half by saying nothing.
 *
 * @param array<Stmt> $statements
 *
 * @return list<FuncCall|MethodCall>
 */
function expectCalls(array $statements): array
{
    $found = [];

    /** @var list<FuncCall> $functions */
    $functions = new NodeFinder()->findInstanceOf($statements, FuncCall::class);

    foreach ($functions as $call) {
        if ($call->name instanceof Name && $call->name->toString() === 'expect') {
            $found[] = $call;
        }
    }

    /** @var list<MethodCall> $methods */
    $methods = new NodeFinder()->findInstanceOf($statements, MethodCall::class);

    foreach ($methods as $call) {
        if ($call->name instanceof Identifier && $call->name->toString() === 'expect') {
            $found[] = $call;
        }
    }

    return $found;
}

/**
 * Method names called on the statement holding this expectation.
 *
 * @param array<Stmt> $statements
 *
 * @return list<string>
 */
function chainAround(array $statements, FuncCall|MethodCall $call): array
{
    $names = [];

    /** @var list<MethodCall> $methods */
    $methods = new NodeFinder()->findInstanceOf($statements, MethodCall::class);

    foreach ($methods as $method) {
        if ($method->getStartLine() <= $call->getStartLine() && $method->getEndLine() >= $call->getEndLine()) {
            $names[] = $method->name instanceof Identifier ? $method->name->toString() : '';
        }
    }

    return $names;
}

/**
 * @return list<string>
 */
function stringsIn(?Node $node): array
{
    if ($node instanceof String_) {
        return [$node->value];
    }

    if (! $node instanceof Array_) {
        return [];
    }

    $found = [];

    foreach ($node->items as $item) {
        if ($item->value instanceof String_) {
            $found[] = $item->value->value;
        }
    }

    return $found;
}

it('R3 — no expectation is narrowed by holding more than one symbol', function (): void {
    $expectations = archExpectations();
    $offenders = [];

    expect($expectations)->not->toBe([], 'no architecture expectation was read, so this rule read nothing');

    foreach ($expectations as $expectation) {
        $narrowing = array_intersect($expectation['methods'], ['toBeUsedIn', 'toOnlyBeUsedIn']);

        if ($expectation['plural'] && $narrowing !== []) {
            $offenders[] = sprintf('%s:%d', $expectation['file'], $expectation['line']);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These expectations report only code that uses every symbol listed:\n  %s\n\n"
        . 'A list on the left of toBeUsedIn is read as "uses all of these", so a file '
        . 'naming one of them passes — which is the violation the rule exists to catch. '
        . 'Write one rule per symbol, in a loop, and name each one after the symbol so '
        . 'the failure says which (R3).',
        implode("\n  ", $offenders),
    ));
});

it('R3 — no expectation mixes a function name with a namespace', function (): void {
    $expectations = archExpectations();
    $offenders = [];

    expect($expectations)->not->toBe([], 'no architecture expectation was read, so this rule read nothing');

    foreach ($expectations as $expectation) {
        $functions = array_filter($expectation['expected'], static fn(string $s): bool => ! namespaceLike($s));
        $namespaces = array_filter($expectation['expected'], namespaceLike(...));

        if ($functions !== [] && $namespaces !== []) {
            $offenders[] = sprintf('%s:%d', $expectation['file'], $expectation['line']);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These expectations hold a function name and a namespace at once:\n  %s\n\n"
        . 'The two are resolved differently and one list cannot hold both: the '
        . 'expectation reports nothing at all, including the namespace that would have '
        . 'failed on its own. Split them (R3).',
        implode("\n  ", $offenders),
    ));
});

it('R3 — every namespace an expectation names resolves to something', function (): void {
    /** @var array<string, array<int, string>> $registered */
    $registered = require Tree::at('vendor/composer/autoload_psr4.php');
    $prefixes = array_keys($registered);
    $expectations = archExpectations();
    $offenders = [];

    expect($expectations)->not->toBe([], 'no architecture expectation was read, so this rule read nothing');

    foreach ($expectations as $expectation) {
        foreach ($expectation['expected'] as $named) {
            if (! namespaceLike($named) || ! isAncestorOfAPrefix($named, $prefixes)) {
                continue;
            }

            $offenders[] = sprintf('%s:%d names %s', $expectation['file'], $expectation['line'], $named);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These name a namespace nothing is registered under:\n  %s\n\n"
        . 'Pest matches a string against the autoloader\'s registered PSR-4 prefixes. A '
        . 'parent of one — `Modules` above `Modules\\Health\\`, `Native` above '
        . '`Native\\Mobile\\` — matches no files, so the expectation checks nothing and '
        . 'reports a green tick. Name the registered prefix, or read the imports '
        . 'directly the way the module boundary rules do (R3).',
        implode("\n  ", $offenders),
    ));
});

/** A namespace has a separator or a capital; a function name has neither. */
function namespaceLike(string $symbol): bool
{
    return str_contains($symbol, '\\') || preg_match('/^[A-Z]/', $symbol) === 1;
}

/**
 * Whether a namespace sits above a registered prefix without being one.
 *
 * A namespace nothing has registered at all — `Lemonfiber\Sdk` before the SDK is
 * installed — is not this problem: it names a package that is not here yet and
 * the rule holds the moment it arrives. The failure is specifically a parent,
 * because a parent looks like it covers its children and covers nothing.
 *
 * @param array<int, string> $prefixes
 */
function isAncestorOfAPrefix(string $namespace, array $prefixes): bool
{
    $self = sprintf('%s\\', $namespace);

    foreach ($prefixes as $prefix) {
        if ($prefix === $self) {
            return false;
        }
    }
    return array_any($prefixes, fn(string $prefix): bool => str_starts_with($prefix, $self));
}
