<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stream;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\WentWrong;
use Modules\Kernel\Api\WhatIsRunning;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatWasSaid;
use Tests\Support\ApiSurface;
use Tests\Support\Module;

/**
 * A member is not shown what the operator is shown.
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
 * Four of the five clauses name types here. The fifth is credentials, and it is
 * the one that needs care, because a member surface that could name none of
 * them could not ask a stack anything at all.
 *
 * {@see Credential} is refused outright. It is the password, it exists for one
 * exchange, and no member screen performs that exchange — signing in belongs to
 * the surface that pairs a machine.
 *
 * {@see Session} is not on the list, and the second rule below is why it does
 * not need to be. Every screen that reads a stack is handed one, so refusing
 * the type would refuse the surface rather than the credential. What the
 * argument at the top is really about is *readings* — a presenter handed a
 * `Report` can pick a fourth line out of it later — and a session has nothing
 * to pick out: `forTheHeader()` is its only accessor and it is named for its
 * one destination, `__debugInfo` and `jsonSerialize` redact it, and
 * `AValueWithOneDestinationTest` is what keeps that true. So the line is drawn
 * where the risk actually is: a member surface may be **handed** a session to
 * ask with, and may never **keep** one or **hand one on**.
 *
 * Lifecycle controls and logs were once absent from this list because there was
 * nothing on this side to refuse. They arrived with the logs and the verbs, and
 * they went in here rather than into a second rule — which is what this
 * paragraph asked for while they were missing. What makes the lifecycle half
 * worth the care is that its types are not only a reading: {@see Supervising}
 * and {@see AgreedTo} *act*, so a household surface that could name them could
 * stop a service the house is watching.
 *
 * `WentWrong` is on the list for a neighbouring rule rather than for this one. A
 * member whose request failed on a stack fault is told it did not work and that
 * the operator has been told, and is **not** shown the fault — and `WentWrong`
 * is exactly the fault, the code and meaning and remedies the core gave.
 *
 * {@see Obstacle} is deliberately absent. A stack that cannot be reached is not
 * a fault being shown to a member: they are told plainly that asking
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
    Supervising::class => 'lifecycle controls',
    WhatToDoWithIt::class => 'lifecycle controls',
    AgreedTo::class => 'lifecycle controls',
    WhatIsRunning::class => 'lifecycle controls',
    Daemons::class => 'lifecycle controls',
    Daemon::class => 'lifecycle controls',
    HowAServiceRuns::class => 'lifecycle controls',
    HowTheStackIsRunning::class => 'lifecycle controls',
    Saying::class => 'logs',
    Scrollback::class => 'logs',
    WhatWasSaid::class => 'logs',
    Said::class => 'logs',
    Stream::class => 'logs',
    LookingFor::class => 'logs',
    HowManyLines::class => 'logs',
];

it('N3-R9 — every type this refuses is one this application has', function (): void {
    // `Foo::class` is a string the compiler builds out of the `use` above it,
    // and it resolves whether or not anything of that name exists. So a
    // renamed type leaves a row here that matches nothing, and the rule below
    // goes on passing while protecting one fewer thing than it says it does —
    // which is worse than not listing it, because a reader stops checking.
    //
    // The household module holds no code yet, so that rule iterates over
    // nothing and the only thing standing between this list and a typo is this
    // case.
    $gone = [];

    foreach (array_keys(NEVER_SHOWN_TO_A_MEMBER) as $name) {
        if (! class_exists($name) && ! interface_exists($name) && ! enum_exists($name)) {
            $gone[] = sprintf('%s, which is %s', $name, NEVER_SHOWN_TO_A_MEMBER[$name]);
        }
    }

    expect($gone)->toBe([], sprintf(
        "This rule refuses types this application does not have:\n  %s\n\n"
        . 'Find what each was renamed to and name it here. A row that matches nothing is a '
        . "clause of `N3-R9` that is written down and not enforced.\n",
        implode("\n  ", $gone),
    ));
});

it('N3-R9 — no member surface keeps a session or hands one on', function (): void {
    // The credentials clause, drawn where it belongs. A screen is handed a
    // session to ask a stack with, which is every screen in the application and
    // not a thing a member does differently. What a member surface must never
    // do is hold one — a field outlives the call it was resumed for — or answer
    // with one, which is how a credential leaves the one method that needed it
    // and becomes something a template can reach.
    $held = [];

    foreach (everyClassOnTheMemberSurface() as $name) {
        $class = ApiSurface::reflect($name);

        foreach ($class->getProperties() as $property) {
            if (in_array(Session::class, ApiSurface::namesIn($property->getType()), strict: true)) {
                $held[] = sprintf('%s::$%s keeps a session', $class->getName(), $property->getName());
            }
        }

        foreach ($class->getMethods() as $method) {
            if (in_array(Session::class, ApiSurface::namesIn($method->getReturnType()), strict: true)) {
                $held[] = sprintf('%s hands one on', ApiSurface::describe($method));
            }
        }
    }

    expect(everyClassOnTheMemberSurface())
        ->not->toBe([], 'the household module holds no classes, so this rule proved nothing');

    expect($held)->toBe([], sprintf(
        "A member surface keeps a credential or passes one along:\n  %s\n\n"
        . 'A session is handed to a screen so it can ask, and that is the whole of what a '
        . "surface may do with one.\n"
        . 'Take it as an argument and use it in the call that needed it (N3-R9).',
        implode("\n  ", $held),
    ));
});

/**
 * Every class the household module holds.
 *
 * Its own reading rather than the one inside the rule below, because both rules
 * want it and a second copy of the filter is a second thing that can match
 * nothing — which is the state either of them would pass in.
 *
 * @return list<class-string>
 */
function everyClassOnTheMemberSurface(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        if ($module->namespace !== 'Modules\\Household') {
            continue;
        }

        foreach ($module->classNames() as $name) {
            $found[] = $name;
        }
    }

    return $found;
}

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
            foreach (ApiSurface::namedBy(ApiSurface::reflect($name)) as [$where, $named]) {
                foreach (array_intersect($named, array_keys(NEVER_SHOWN_TO_A_MEMBER)) as $shown) {
                    $found[] = sprintf(
                        '%s names %s, which is %s',
                        $where,
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
