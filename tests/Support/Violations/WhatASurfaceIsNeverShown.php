<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** The requirement rows a screen or a report must not break by what it shows. */
final readonly class WhatASurfaceIsNeverShown
{
    /**
     * The requirements a surface is held to by what it may not name.
     *
     * Each of these was driven by hand when it was written, which proves it once
     * and on one machine. Here it is proved on every run, which is what the
     * harness is for.

     *
     * @return list<Fixture>
     */
    public static function whatASurfaceIsNeverShown(): array
    {
        return [
            Fixture::suite('N2-R12', 'app-modules/health/src/Internal/Screens/ShowsACredential.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal\Screens;

                use Modules\Kernel\Api\Credential;

                final readonly class ShowsACredential
                {
                    public function secret(Credential $credential): string
                    {
                        return '';
                    }
                }
                PHP, 'N2-R12 — no screen can be handed a credential'),

            Fixture::suite('N3-R9', 'app-modules/household/src/Fixtures/ShowsAReport.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Household\Fixtures;

                use Modules\Kernel\Api\Report;

                final readonly class ShowsAReport
                {
                    public function threeLinesFrom(Report $report): string
                    {
                        return '';
                    }
                }
                PHP, 'N3-R9 — nothing on a member surface can be handed'),

            // The queue `ADR-0020` spends its length rejecting, written the way
            // every collection in this repository is written: a promoted
            // constructor parameter with its shape on the constructor's
            // `@param`, and the element type led with by an `@implements`.
            // `Findings` is the model it copies, which is what makes this the
            // spelling a queue would actually arrive in.
            Fixture::suite('N1-R41', 'app-modules/health/src/Fixtures/HoldsUndelivered.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                use ArrayIterator;
                use IteratorAggregate;
                use Modules\Kernel\Api\Attempted;
                use Traversable;

                /** @implements IteratorAggregate<int, Attempted> */
                final readonly class HoldsUndelivered implements IteratorAggregate
                {
                    /** @param array<int, Attempted> $waiting */
                    private function __construct(private array $waiting) {}

                    public static function of(Attempted ...$waiting): self
                    {
                        return new self(array_values($waiting));
                    }

                    public function getIterator(): Traversable
                    {
                        return new ArrayIterator($this->waiting);
                    }
                }
                PHP, 'N1-R41 — nothing holds a collection of actions', 'HoldsUndelivered'),

            // Filed under the row that replaced the withdrawn one. The fixture
            // is unchanged: what the rule reads for is still every player,
            // which is stronger than the surviving rows ask and is held while
            // nothing on the wire says where to play a holding.
            Fixture::suite('N3-R14', 'bridge/resources/android/PlaysMedia.kt', <<<'KOTLIN'
                package app.lemonfiber.native

                class PlaysMedia(private val context: Context) {
                    fun play(url: String) {
                        val player = ExoPlayer.Builder(context).build()
                    }
                }
                KOTLIN, 'N3-R14 — no platform source reaches for a media player'),

            // A test's own title, in the Kotlin harness, which is the half of
            // that rule the ratchet over the PHP deliberately does not hold: a
            // PHP test's description is read by a runner, and a backticked
            // Kotlin function name is the sentence somebody reads in the file.
            // Planting it here rather than as a comment also keeps it out of the
            // ratchet's count, which reads every PHP file including this one.
            Fixture::suite('GOV-R6', 'bridge/android/src/test/kotlin/NamesARequirementTest.kt', <<<'KOTLIN'
                package app.lemonfiber.native

                import kotlin.test.Test

                class NamesARequirementTest {
                    @Test
                    fun `N4-R9 - a backgrounded app is protected`() {
                    }
                }
                KOTLIN, 'GOV-R6 — no Kotlin or Swift source names a requirement'),

            // The violation is a method added to a type this repository already
            // has, so there is no file to drop in beside it — which is why this
            // was answered with a paragraph for as long as the harness could
            // only write whole files. `Session` is the narrower of the two
            // subjects: a token scoped to an address is the exact change the
            // requirement forbids, made for a transport reason, and it is the
            // one somebody reaches for the first time two stacks are reachable
            // at once.
            Fixture::edit(
                'N1-R14',
                'app-modules/kernel/src/Api/Session.php',
                '    /** What `json_encode` writes. */',
                <<<'PHP'
                        /** A session, narrowed to the one stack it was issued by. */
                        public function scopedTo(Address $where): self
                        {
                            return $this;
                        }

                        /** What `json_encode` writes. */
                    PHP,
                'a type that must not reach another cannot name it in any signature',
                'scopedTo',
            ),

            // One per subject rather than one entry naming three requirements.
            // The three are the same shape and not the same claim: each type is
            // named for a different destination, and a fixture that broke one
            // and spoke for all three would be the rule claiming more than it
            // enforces — which is the thing this harness exists to catch.
            Fixture::edit(
                'N1-R8',
                'app-modules/kernel/src/Api/Session.php',
                '    /**
     * Whether this is the same session, compared in constant time.',
                <<<'PHP'
                        /** The session, for a query string this time. */
                        public function forTheQuery(): string
                        {
                            return $this->token;
                        }

                        /**
                         * Whether this is the same session, compared in constant time.
                    PHP,
                'a value with one destination publishes one way to reach it',
                'forTheQuery',
            ),

            Fixture::edit(
                'N1-R7',
                'app-modules/kernel/src/Api/Credential.php',
                '    /**
     * Whether this has been exchanged already.',
                <<<'PHP'
                        /** The secret, without spending it. */
                        public function value(): string
                        {
                            return (string) $this->secret;
                        }

                        /**
                         * Whether this has been exchanged already.
                    PHP,
                'a value with one destination publishes one way to reach it',
                'value',
            ),

            Fixture::edit(
                'N1-R15',
                'app-modules/kernel/src/Api/Address.php',
                '    /** What `json_encode` writes. */',
                <<<'PHP'
                        /** The address, for whatever wants to print it. */
                        public function shown(): string
                        {
                            return $this->url;
                        }

                        /** What `json_encode` writes. */
                    PHP,
                'a value with one destination publishes one way to reach it',
                'shown',
            ),

            // A stack's address on the glass, which is what somebody with two
            // stacks reaches for to tell them apart — and is the one thing on a
            // stack that must never reach a screen. Nothing else in this
            // repository can see it: PHPStan does not read Blade, and the
            // architecture rules reflect over classes.
            Fixture::edit(
                'F7',
                'app-modules/operator/resources/views/your-stacks.blade.php',
                '{{ $stack->name()->shown() }}',
                '{{ $stack->at()->forTheClient() }}',
                'F7 —',
                'forTheClient',
            ),

            // A key the catalogue does not hold, in a template, which is where
            // one is least visible: Blade is not PHP any analyser reads, so
            // nothing else in this repository can see a string here at all.
            // `L2` cannot either — it compares the two catalogues against each
            // other, and they agree perfectly about a key neither of them has.
            //
            // Drift rather than a misspelling, which is the commoner way this
            // happens and the one a spell-checker cannot help with: the line was
            // renamed in `lang/` and the template that reads it was not. A
            // planted misspelling would also make the typos gate red, for a
            // word that is deliberately wrong.
            // An enum that derives its own catalogue keys and is not listed in
            // `everyDerivedKey()`. The table there is maintained by hand for a
            // good reason — a scan would have to guess which methods return
            // keys — and this is the gap that leaves: an enum written after the
            // table is invisible to every rule in the suite, including the one
            // that checks derived keys resolve. It can ship with no catalogue
            // line at all and the operator is shown the key itself.
            //
            // `Waiting` was exactly this shape, with seven cases building
            // `household.<value>` against a catalogue file that did not exist.
            // Nothing caught it, which is why the scan exists and why it has a
            // fixture of its own rather than being trusted to keep working.
            Fixture::suite(
                'L7',
                'app-modules/health/src/Api/Fixtures/Unlisted.php',
                <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace Modules\Health\Api\Fixtures;

                    use function sprintf;

                    enum Unlisted: string
                    {
                        case Something = 'something';

                        public function saidOnTheScreen(): string
                        {
                            return sprintf('health.%s', $this->value);
                        }
                    }
                    PHP,
                'L7 —',
                'Modules\Health\Api\Fixtures\Unlisted',
            ),

            Fixture::edit(
                'L7',
                'app-modules/operator/resources/views/your-stacks.blade.php',
                "{{ __('connection.pair') }}",
                "{{ __('connection.pair_up') }}",
                'L7 —',
                'connection.pair_up',
            ),

            // Nothing to drop in: a listener is only a listener once something
            // has registered it, and the dispatcher is what this rule reads.
            // Neither class needs to exist — `getRawListeners()` hands back the
            // strings it was given, and the rule compares namespaces — so the
            // whole violation is the one call that registers them.
            Fixture::edit(
                'E5',
                'bootstrap/Composition/CompositionRoot.php',
                '        $this->app->booted(TheTheme::paint(...));',
                <<<'PHP'
                            \Illuminate\Support\Facades\Event::listen(
                                'Modules\Backups\Api\Events\ArchiveWritten',
                                'Modules\Health\Internal\ListensAcrossAKind@handle',
                            );

                            $this->app->booted(TheTheme::paint(...));
                    PHP,
                'E5 — no listener reacts to an event its module may not name',
                'ListensAcrossAKind',
            ),

            // The shape half: an accessor that asks for no closure hands over
            // the value with the age left behind, which is the requirement
            // broken by omission rather than by anything anybody wrote down.
            Fixture::edit(
                'N1-R9, N2-R13',
                'app-modules/kernel/src/Api/Reading.php',
                '    /** Held from an earlier session, and this is when it was read. */',
                <<<'PHP'
                        /** What it holds, with nothing said about when it was read. */
                        public function valueWithoutItsAge(): object
                        {
                            return $this->value;
                        }

                        /** Held from an earlier session, and this is when it was read. */
                    PHP,
                'N1-R9, N2-R13 — the value is reachable only by saying what happens either way',
                'valueWithoutItsAge',
            ),

            // The annotation half, which the shape rule cannot see: `either()`
            // keeps both closures and stops promising the retained one the
            // moment it was read. A screen is written against the annotation,
            // so the annotation is what is checked.
            Fixture::edit(
                'N1-R9',
                'app-modules/kernel/src/Api/Reading.php',
                '     * @param Closure(object, Instant): TRetained $retained',
                '     * @param Closure(object): TRetained $retained',
                'N1-R9 — the retained arm is handed the moment it was read',
                'Closure(object, Instant): TRetained',
            ),
        ];
    }
}
