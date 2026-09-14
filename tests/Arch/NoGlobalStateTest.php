<?php

declare(strict_types=1);

use Tests\Support\Module;
use Tests\Support\Tree;

// A6 / I1 — no mutable global state.
//
// Pest's architecture expectations have no rule for this, so rather than assume
// one and get a silently skipped check, this asks PHP directly. The reason it
// matters more here than in a web application: NativePHP runs a persistent
// process, not a request. A static cache that a web server would harmlessly
// rebuild on the next request survives between screens here, and becomes a
// stale answer on someone's phone long after the thing it cached changed.
//
// **Two declarations, and they are not the same reading.** A static property is
// a fact about a class, so reflection answers it. A `static` inside a method
// body is a fact about nothing reflection exposes — it has no property, no
// name a class knows, and no entry in any API — and it survives a dispatch for
// precisely the same reason and just as completely. A rule that asked only the
// first would be the wider sentence over the narrower mechanism, with the gap
// sitting where a cache is most naturally written.
//
// So the second is read over tokens, and over more files than the first: the
// composition root and the plugin's own PHP run in the same persistent process,
// and a file whose declared name does not match its path is skipped by class
// discovery while still being loaded and run.

it('A6/I1 — declares no static property anywhere in a module', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $reflection = new ReflectionClass($name);

            foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property) {
                if ($property->getDeclaringClass()->getName() !== $name) {
                    continue;  // inherited from a framework base class, not ours
                }

                $offenders[] = sprintf('%s::$%s', $name, $property->getName());
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These hold state that survives a dispatch:\n  %s",
        implode("\n  ", $offenders),
    ));
});

it('A6/I1 — nor a static variable inside a method', function (): void {
    $offenders = [];

    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('native/src'), '.php'),
    ];

    foreach ($sources as $path) {
        // Tests hold their own state and are torn down with the run. What this
        // is about is state in the process the operator is looking at.
        if (str_contains($path, '/tests/')) {
            continue;
        }

        foreach (staticVariablesIn((string) file_get_contents($path)) as $line) {
            $offenders[] = sprintf('%s:%d', str_replace(sprintf('%s/', Tree::root()), '', $path), $line);
        }
    }

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These keep a value between calls, in a process that does not end:\n  %s\n\n"
        . 'A `static` in a method body is the same thing as a static property and is '
        . 'invisible to reflection, so the rule above reads straight past it. The runtime '
        . 'here is persistent (I1): what it holds is still held when the operator opens '
        . "the next screen, and still held after the thing it cached has changed.\n"
        . 'Hand the value in, or put it behind a port with a fake — memoising inside a '
        . 'method is a cache nothing can clear and no test can arrange (A6, I1).',
        implode("\n  ", $offenders),
    ));
});

/**
 * The lines on which a source declares a `static` variable.
 *
 * `static` beside a variable name and nothing else: `static function`,
 * `static fn`, `new static` and `static::` each put a different token next, so
 * the one shape left is the declaration. A static *property* written without a
 * type would land here too, which is a second report of something the rule
 * above already names rather than a wrong one.
 *
 * @return list<int>
 */
function staticVariablesIn(string $source): array
{
    $tokens = token_get_all($source);
    $found = [];

    foreach ($tokens as $at => $token) {
        if (! is_array($token) || $token[0] !== T_STATIC) {
            continue;
        }

        if (isTheNextToken($tokens, $at, T_VARIABLE)) {
            $found[] = $token[2];
        }
    }

    return $found;
}

/**
 * Whether the next token that carries meaning is of the given kind.
 *
 * @param list<array{int, string, int}|string> $tokens
 */
function isTheNextToken(array $tokens, int $at, int $kind): bool
{
    $counter = count($tokens);
    for ($here = $at + 1; $here < $counter; $here++) {
        $next = $tokens[$here];

        if (! is_array($next)) {
            return false;
        }

        if (in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], strict: true)) {
            continue;
        }

        return $next[0] === $kind;
    }

    return false;
}
