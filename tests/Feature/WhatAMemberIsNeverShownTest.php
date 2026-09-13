<?php

declare(strict_types=1);

use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WentWrong;
use Tests\Support\ApiSurface;
use Tests\Support\Module;

/**
 * `N3-R9` — a member is not shown what the operator is shown.
 *
 * > A member MUST NOT be shown lifecycle controls, logs, credentials,
 * > diagnostics, or another member's requests.
 *
 * The household module is where a member's surface goes and it holds no code
 * yet, which makes this the moment the rule is worth writing rather than the
 * moment it is too early. A rule added afterwards is a rule written around
 * whatever is already there, and what would already be there is a screen that
 * renders a `Report` because a `Report` was what the caller had.
 *
 * Diagnostics is the clause with types to name today. The others are held
 * elsewhere or not yet buildable: a credential cannot reach any screen at all
 * (`N2-R12`), and lifecycle controls and logs have no types on this side to
 * refuse — when they arrive, they belong in the list below rather than in a
 * second rule.
 *
 * `WentWrong` is on the list for `N3-R10` rather than for this requirement. A
 * member whose request failed on a stack fault is told it did not work and that
 * the operator has been told, and is **not** shown the fault — and `WentWrong`
 * is exactly the fault, the code and meaning and remedies the core gave.
 *
 * {@see Obstacle} is deliberately absent. A stack that cannot be reached is not
 * a fault being shown to a member: `N3-R12` has them told plainly that asking
 * for something new is declined while it is unreachable, which needs the reason
 * rather than hides it.
 *
 * The refusal is on the module rather than on its screens, and that is
 * deliberate. A member's surface is not only what renders: a presenter that
 * takes a `Report` to pick three lines out of it has already brought the whole
 * report into the household module, and the screen that renders the fourth line
 * is a later edit nobody reviews as a policy change.
 */

/** What a member is never shown, and what the requirement calls it. */
const NEVER_SHOWN_TO_A_MEMBER = [
    Report::class => 'diagnostics',
    Findings::class => 'diagnostics',
    Finding::class => 'diagnostics',
    Problem::class => 'diagnostics',
    Overall::class => 'diagnostics',
    Conclusion::class => 'diagnostics',
    WentWrong::class => 'the fault behind a failed request',
    Credential::class => 'credentials',
    Session::class => 'credentials',
];

it('N3-R9 — nothing on a member surface can be handed what the operator is shown', function (): void {
    $household = array_values(array_filter(
        Module::all(),
        static fn(Module $module): bool => $module->namespace === 'Modules\\Household',
    ));

    // Assert the reading before what it says. A filter that matched no module
    // would make this pass about nothing, which is the state it exists to
    // refuse in everything else.
    expect($household)->not->toBe([], 'the household module was not found, so this rule proved nothing');

    $found = [];

    foreach ($household as $module) {
        foreach ($module->classNames() as $name) {
            $class = new ReflectionClass($name);

            foreach ($class->getMethods() as $method) {
                $named = ApiSurface::namesIn($method->getReturnType());

                foreach ($method->getParameters() as $parameter) {
                    $named = [...$named, ...ApiSurface::namesIn($parameter->getType())];
                }

                foreach ($class->getProperties() as $property) {
                    $named = [...$named, ...ApiSurface::namesIn($property->getType())];
                }

                foreach (array_intersect($named, array_keys(NEVER_SHOWN_TO_A_MEMBER)) as $shown) {
                    $found[] = sprintf(
                        '%s names %s, which is %s',
                        ApiSurface::describe($method),
                        $shown,
                        NEVER_SHOWN_TO_A_MEMBER[$shown],
                    );
                }
            }
        }
    }

    expect(array_values(array_unique($found)))->toBe([], sprintf(
        "A member surface can reach what only the operator is shown:\n  %s\n\n"
        . 'N3-R9 draws the line at the surface rather than at the screen: a presenter '
        . 'that takes a report to pick three lines out of it has brought the whole '
        . "report here, and the fourth line is a later edit nobody reviews.\n"
        . 'What a member sees about their own requests is their state in household '
        . 'terms (N3-R6), which is a different answer from the core rather than a '
        . 'narrower reading of this one.',
        implode("\n  ", $found),
    ));
});
