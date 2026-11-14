<?php

declare(strict_types=1);

use Lemonfiber\Companion\PHPStan\Rules\OneHome\Clash;
use Lemonfiber\Companion\PHPStan\Rules\OneHome\Reach;
use Tests\Support\Tree;

/**
 * What a collector gathers of one constant in one file.
 *
 * @return array<string, list<list<array{string, string, int, bool}>>>
 */
function oneConstantIn(string $file, string $name, string $value): array
{
    return [Tree::at($file) => [[[$value, $name, 1, false]]]];
}

/** @return list<string> */
function theClashesAmong(string $value, string ...$homes): array
{
    $collected = [];

    foreach ($homes as $at => $file) {
        $collected = [...$collected, ...oneConstantIn($file, sprintf('Home%d::VALUE', $at), $value)];
    }

    return array_map(static fn(Clash $clash): string => $clash->home->name, Clash::among($collected, [], Reach::declared()));
}

it('refuses a value declared twice in one module', function (): void {
    expect(theClashesAmong("'heartbeat'", 'app-modules/health/src/One.php', 'app-modules/health/src/Two.php'))->toBe(['Home0::VALUE', 'Home1::VALUE']);
});

it('refuses a value declared in a module and again in one it requires', function (): void {
    expect(theClashesAmong("'heartbeat'", 'app-modules/operator/src/One.php', 'app-modules/kernel/src/Api/Two.php'))->toBe(['Home0::VALUE', 'Home1::VALUE']);
});

it('refuses a value the composition root or the suites\' support declares again', function (): void {
    expect(theClashesAmong("'heartbeat'", 'bootstrap/Composition/One.php', 'app-modules/vault/src/Two.php'))->toBe(['Home0::VALUE', 'Home1::VALUE'])
        ->and(theClashesAmong("'heartbeat'", 'tests/Support/One.php', 'bridge/src/Two.php'))->toBe(['Home0::VALUE', 'Home1::VALUE']);
});

it('leaves alone equal values in modules that cannot refer to each other', function (): void {
    expect(theClashesAmong("'last'", 'app-modules/sdk/src/Api/One.php', 'app-modules/vault/src/Internal/Two.php'))->toBe([])
        ->and(theClashesAmong("'chosen_by'", 'app-modules/sdk/src/Api/One.php', 'app-modules/watching/src/Internal/Two.php'))->toBe([])
        ->and(theClashesAmong("'offered'", 'bridge/src/One.php', 'app-modules/sdk/src/Api/Two.php'))->toBe([]);
});
