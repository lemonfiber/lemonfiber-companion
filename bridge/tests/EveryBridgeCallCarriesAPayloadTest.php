<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

// On a handset `nativephp_call` is a C extension that takes exactly two
// arguments. The fallback NativePHP loads everywhere else declares a default
// for the second, so a call with one argument passes every test here and is a
// fatal on the phone. This reads the shipped sources and holds every call to
// two.

/**
 * The calls to the bridge in one file that pass anything but two arguments, as file:line.
 *
 * @return list<string>
 */
function bridgeCallsWithoutAPayloadIn(string $path): array
{
    $ast = new ParserFactory()->createForHostVersion()->parse((string) file_get_contents($path));

    $short = new NodeFinder()->find($ast ?? [], static fn(Node $node): bool => $node instanceof FuncCall
        && $node->name instanceof Name
        && $node->name->getLast() === 'nativephp_call'
        && count($node->args) !== 2);

    return array_values(array_map(
        static fn(Node $call): string => sprintf('%s:%d', basename($path), $call->getStartLine()),
        $short,
    ));
}

it('passes a payload on every call to the bridge', function (): void {
    $paths = glob(sprintf('%s/src/*.php', dirname(__DIR__)));
    $files = $paths === false ? [] : $paths;

    expect($files)->not->toBeEmpty()
        ->and(array_merge(...array_map(bridgeCallsWithoutAPayloadIn(...), $files)))->toBe([]);
});
