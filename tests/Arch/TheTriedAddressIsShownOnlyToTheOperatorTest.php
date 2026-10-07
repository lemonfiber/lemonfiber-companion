<?php

declare(strict_types=1);

use Tests\Support\Tree;

// The address a reach that met nothing was tried at is shown to the operator,
// on the screen that says the stack was not reached, and goes nowhere else:
// never on a member's screen, never in a log, never in a diagnostic report.
//
// Kept by who may read it. The address's accessor for that screen is read by
// the obstacle that carries it and nothing else; the obstacle's is read by the
// operator's view model and the one component that draws it. A log line, a
// report or a member's screen would each have to name one of the two, and
// naming either anywhere else is what this refuses.

/** Who may read each accessor, outside the file that declares it, in sorted order. */
const MAY_READ_WHERE_IT_WAS_TRIED = [
    'forTheOperatorWhoCouldNotReachIt' => [
        'app-modules/kernel/src/Api/Obstacle.php',
    ],
    'whereItWasTried' => [
        'app-modules/operator/resources/views/components/what-stood-in-the-way.blade.php',
        'app-modules/operator/src/Internal/ViewModels/HowTheReadingWent.php',
    ],
];

/** The files that declare each accessor. */
const DECLARES_WHERE_IT_WAS_TRIED = [
    'forTheOperatorWhoCouldNotReachIt' => 'app-modules/kernel/src/Api/Address.php',
    'whereItWasTried' => 'app-modules/kernel/src/Api/Obstacle.php',
];

/**
 * Every file the application ships with that could name an accessor: its
 * source, its templates and its composition, and none of its tests.
 *
 * @return list<string> paths from the root of the repository
 */
function whatTheApplicationShipsWith(): array
{
    $files = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('resources'), '.php'),
    ];

    $shipped = [];

    foreach ($files as $file) {
        $relative = str_replace(sprintf('%s/', Tree::root()), '', $file);

        if (! str_contains($relative, '/tests/')) {
            $shipped[] = $relative;
        }
    }

    return $shipped;
}

it('reads each accessor only where the operator is shown the address', function (string $accessor): void {
    $shipped = whatTheApplicationShipsWith();
    $readers = [];

    foreach ($shipped as $file) {
        $said = (string) file_get_contents(sprintf('%s/%s', Tree::root(), $file));

        if (str_contains($said, sprintf('%s(', $accessor)) && $file !== DECLARES_WHERE_IT_WAS_TRIED[$accessor]) {
            $readers[] = $file;
        }
    }

    sort($readers);

    expect($shipped)->not->toBe([])
        ->and($readers)->toBe(MAY_READ_WHERE_IT_WAS_TRIED[$accessor], sprintf(
            "`%s()` is read somewhere other than the operator's screen that says a stack was not reached.\n"
            . 'The address it answers with is where somebody lives: it is shown to the operator '
            . 'there, and never on a member\'s screen, in a log or in a diagnostic report.',
            $accessor,
        ));
})->with(array_keys(MAY_READ_WHERE_IT_WAS_TRIED));

it('draws no address on any screen a member is shown', function (): void {
    // The household's templates and view models, named whole: a member's
    // screen has no way to the address because nothing under it names the
    // accessor, and the rule above already refuses one that does.
    $household = array_filter(
        whatTheApplicationShipsWith(),
        static fn(string $file): bool => str_starts_with($file, 'app-modules/household/'),
    );

    expect($household)->not->toBe([]);

    foreach ($household as $file) {
        $said = (string) file_get_contents(sprintf('%s/%s', Tree::root(), $file));

        expect($said)->not->toContain('connection.tried_at', $file)
            ->and($said)->not->toContain('what-stood-in-the-way', $file);
    }
});
