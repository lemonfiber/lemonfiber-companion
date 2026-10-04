<?php

declare(strict_types=1);

use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheStackEdits;
use Tests\Support\Tree;

// A value the operator changed is never offered back one file or one value at
// a time.
//
// What the operator edited reaches this app as a stack file kept as they left
// it, or a connection the wiring kept as theirs. Nothing a port takes can name
// one of those to put back: the one way lemonfiber's own goes back over an
// operator's is the reset, every edit at once, on a yes that can only be built
// from a preview naming what it would revert.

/** The types an operator's change reaches this app as. */
const WHAT_AN_OPERATORS_CHANGE_ARRIVES_AS = [AStackEdit::class, TheStackEdits::class, AConnection::class];

/**
 * Every port the kernel declares, as an interface.
 *
 * @return list<ReflectionClass<object>>
 */
function everyPortTheKernelDeclares(): array
{
    $ports = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/kernel/src/Api'), '.php') as $path) {
        $name = sprintf('Modules\\Kernel\\Api\\%s', basename($path, '.php'));

        if (interface_exists($name)) {
            $ports[] = new ReflectionClass($name);
        }
    }

    return $ports;
}

it('declares no port that takes an edit or a connection of the operator\'s, so none can put one back', function (): void {
    $ports = everyPortTheKernelDeclares();
    $takesOne = [];

    foreach ($ports as $port) {
        foreach ($port->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && in_array($type->getName(), WHAT_AN_OPERATORS_CHANGE_ARRIVES_AS, strict: true)) {
                    $takesOne[] = sprintf('%s::%s($%s)', $port->getShortName(), $method->getName(), $parameter->getName());
                }
            }
        }
    }

    expect($ports)->not->toBe([])
        ->and($takesOne)->toBe([]);
});

it('puts lemonfiber\'s own back over the operator\'s only through the reset, on a yes agreed to its preview', function (): void {
    $revert = new ReflectionMethod(ResettingTheConfiguration::class, 'revert');

    expect(array_map(
        static fn(ReflectionParameter $parameter): string => (string) $parameter->getType(),
        $revert->getParameters(),
    ))->toBe([Stack::class, Session::class, AResetAgreed::class]);
});
