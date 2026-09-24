<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** The rules answered without a planted file. */
final readonly class Unplanted
{
    /**
     * The rules whose violation is a state of this run rather than a file.
     *
     * Each was `notDrivable` until the judgement was split from the reading
     * around it. Nothing can be planted for these — the run that read the
     * planted thing would be this run — but the judgement can be called with the
     * violation, which is the same proof arriving by the other door.
     *
     * @return list<Fixture>
     */
    public static function drivenDirectly(): array
    {
        return [
            Fixture::direct(
                'N1-R13',
                '`unionIn` and `outcomesIn` in the Feature suite handed envelope text of '
                . 'their own: a union the enum does not match, a field declared twice with '
                . 'unions that disagree, a single literal where a union is wanted, and a '
                . 'field the text never mentions. Nothing could be planted: the envelope is '
                . 'somebody else\'s file in `vendor/`, restored by composer rather than by '
                . 'this harness, and a fixture that failed to clean up would leave the '
                . 'installed SDK wrong.',
            ),
            Fixture::direct(
                'G11',
                '`notTurnedOn` in the Arch suite handed a settings list with one attribute '
                . 'missing, one present but "false", and one element carrying nothing at '
                . 'all — and asked to name each. Nothing could be planted: the settings '
                . 'live in the `phpunit.xml` of the run doing the reading, so taking an '
                . 'attribute out changes that run rather than a fixture, and what the '
                . 'settings produce is an exit code the JUnit report this harness reads '
                . 'records as a test that passed. That second half is still checked by '
                . 'hand: delete `.env.testing` and watch `composer test:mutation` exit 1 '
                . 'where it exited 0.',
            ),
            Fixture::direct(
                'Q-R66 (discovery)',
                '`Tree::isTheRepository` handed the parent of the root — which is what '
                . '`dirname(__DIR__, 2)` answers if the file computing it ever moves one '
                . 'level down — and a temporary directory, and asked to refuse both. '
                . 'Nothing could be planted for this: the root is what every path in the '
                . 'harness is built from, so a fixture would have to be written to a tree '
                . 'the harness could no longer find.',
            ),
            Fixture::direct(
                'R2',
                'Its own judgement — `rulesWithNoFixture` in the Guards suite — handed a '
                . 'rule that claims enforcement and a coverage list without it, and asked '
                . 'to name it. Planting it instead would mean documenting a rule and '
                . 'leaving it uncovered in the repository doing the reading, and the run '
                . 'that read it would be this run.',
            ),
        ];
    }

    /**
     * The rules no snippet can break, each with the reason.
     *
     * @return list<Fixture>
     */
    public static function notDrivable(): array
    {
        return [
            Fixture::notDrivable(
                'S2',
                'A fixture would have to be a dependency with a published advisory, which '
                . 'means installing a vulnerable package on purpose in order to watch '
                . 'resolution refuse it. roave/security-advisories is conflict-only and '
                . 'carries no code, so there is nothing to call either.',
            ),
            Fixture::notDrivable(
                'G4',
                'A shadow dependency needs a package to shadow. The rule is enforced by '
                . 'composer-dependency-analyser, a third tool this harness does not run, and '
                . 'faking a violation would mean installing a package in order to not use it.',
            ),
        ];
    }
}
