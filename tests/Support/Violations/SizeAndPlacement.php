<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** The integrity of the suite, and where a class may live. */
final readonly class SizeAndPlacement
{
    /** @return list<Fixture> */
    public static function sizeAndSuiteIntegrity(): array
    {
        return [
            Fixture::analyser('D6', 'Plain/WaitsThirtySeconds.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WaitsThirtySeconds
                {
                    public function timeout(): int
                    {
                        return 30;
                    }
                }
                PHP, 'D6 —'),

            // In a pass of its own: this is the violation that stops the rest of
            // the run happening, so sharing a pass with the other fixtures would
            // hide every one of them.
            Fixture::isolatedSuite('G6', 'app-modules/health/tests/Fixtures/NarrowedTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('is the only test that will run', function (): void {
                    expect(true)->toBeTrue();
                })->only();
                PHP, 'G6 —', 'NarrowedTest'),

            // The row's other half, and the shape that is not empty brackets.
            // `skip(true)` switches a test off as completely as `skip()` does
            // and reads, to anything looking for `()`, exactly like a test that
            // runs — so the half of the row about reasons needs a fixture that
            // gives a condition and no reason. It shares a pass with the rest:
            // a skipped test changes what the run reports about itself and
            // nothing about what the run examines.
            Fixture::suite('G6', 'app-modules/health/tests/Fixtures/SilentlySkippedTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('is switched off, and says nothing about what it is waiting for', function (): void {
                    expect(true)->toBeTrue();
                })->skip(true);
                PHP, 'G6 —', 'SilentlySkippedTest'),

            Fixture::suite('H7', 'app-modules/health/tests/Fixtures/MiscTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('holds a behaviour', function (): void {
                    expect(true)->toBeTrue();
                });
                PHP, 'H7 —', 'MiscTest'),

            Fixture::suite('G8', 'app-modules/kernel/src/Api/Fixtures/Unbound.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Kernel\Api\Fixtures;

                interface Unbound
                {
                    public function answer(): string;
                }
                PHP, 'G8 —', 'Unbound'),

            // A port with no contract beside it. The same shape as `Unbound`
            // above and reported by a different rule, which is the point: one
            // says nothing implements it in the container, the other says
            // nothing compares the things that do.
            Fixture::suite('G2', 'app-modules/kernel/src/Api/Fixtures/Unproven.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Kernel\Api\Fixtures;

                interface Unproven
                {
                    public function answer(): string;
                }
                PHP, 'G2 —', 'Unproven'),
        ];
    }

    /** @return list<Fixture> */
    public static function placement(): array
    {
        return [
            Fixture::suite('W1', 'bootstrap/Stray.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Bootstrap;

                final readonly class Stray {}
                PHP, 'W1 —', 'Stray'),

            // A namespace no autoloader maps, rather than another real module's:
            // PSR-4 would resolve the same file under two names and PHP would
            // fatal on the second declaration before the rule could report.
            Fixture::suite('W2', 'app-modules/health/src/Fixtures/Borrowed.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Elsewhere\Fixtures;

                final readonly class Borrowed {}
                PHP, 'W2 —', 'Borrowed'),

            Fixture::suite('W3', 'resources/views/Fixtures/stray.blade.php', <<<'BLADE'
                <native:text>a screen with no module</native:text>
                BLADE, 'W3 —', 'stray.blade.php'),

            // W3's other clause. The fixture above is a Blade file in the root
            // view directory; this is a test file in the root `tests/` that is
            // not one of the suites. Root `tests/` holds `Arch`, `Feature`,
            // `Contract` and the rest — a file loose beside them belongs to no
            // suite, so whether it runs depends on which command somebody typed.
            Fixture::suite('W3', 'tests/Fixtures/StrayTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('sits in no suite at all', function (): void {
                    expect(true)->toBeTrue();
                });
                PHP, 'W3 —', 'tests/Fixtures'),

            Fixture::suite('W4', 'app-modules/health/tests/Fixtures/BorrowedTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Elsewhere\Tests\Fixtures;

                it('answers to another module', function (): void {
                    expect(true)->toBeTrue();
                });
                PHP, 'W4 —', 'BorrowedTest'),

            // Two classes in one source file, which is the case `W6` exists
            // for: the first answers for the path and the second is judged by
            // nothing.
            //
            // The second is deliberately well-formed — final, readonly, a name
            // no rule bans — so that sharing a file is the only thing wrong
            // with it and `W6` is the only rule that can refuse this fixture.
            // Making it malformed would prove nothing, and that is the finding
            // rather than a detail of the fixture: a second class is invisible
            // to `every class is final` and to the readonly rule too, so an
            // unsealed one here would be caught by nothing and the fixture
            // would look like a rule that had stopped working.
            //
            // `$evidence` is the hidden class, because the first one is named
            // by the path and would appear in a report either way.
            Fixture::suite('W6', 'app-modules/health/src/Fixtures/TwoInOneFile.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class TwoInOneFile {}

                final readonly class TheSecondOneNobodySees
                {
                    public function __construct(public string $said = '') {}
                }
                PHP, 'W6 —', 'TheSecondOneNobodySees'),

            Fixture::suite('W5', 'app-modules/health/tests/Fixtures/SaysNothingTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Closure;

                it('imports a name into the namespace it is already in', function (): void {
                    expect(Closure::class)->toBe('Closure');
                });
                PHP, 'W5 —', 'SaysNothingTest'),

            // Inside the SDK module, because that is where W7 looks — a reader
            // planted anywhere else would prove the rule green while refusing
            // nothing. It reaches a payload and never names the gate, which is
            // the whole of what the rule refuses.
            Fixture::suite('W7', 'app-modules/sdk/src/Api/Fixtures/ReadsUnchecked.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Sdk\Api\Fixtures;

                use Lemonfiber\Sdk\Envelope\Envelope;

                final readonly class ReadsUnchecked
                {
                    /** @param Envelope<mixed> $envelope */
                    public static function in(Envelope $envelope): mixed
                    {
                        return $envelope->data;
                    }
                }
                PHP, 'W7 —', 'ReadsUnchecked'),

            // A source file, not a test: C10 exempts tests deliberately, so a
            // fixture planted under `tests/` would prove the rule green while
            // refusing nothing.
            // W8's phpstan half, and the reason `Proof::AnalyserInPlace` exists. The
            // rule narrows to the SDK's own sources, so a fixture under the
            // fixture tree — which sits outside every source directory by
            // design — could never make it fire. Planted where the rule looks
            // instead, and swept back out by the same manifest that restores an
            // edited file.
            Fixture::analyserInPlace('W8', 'app-modules/sdk/src/Internal/Fixtures/ASubstitutedValue.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Sdk\Internal\Fixtures;

                final readonly class ASubstitutedValue
                {
                    /** @param array{incomplete?: bool} $said */
                    public function everythingArrived(array $said): bool
                    {
                        return $said['incomplete'] ?? false;
                    }
                }
                PHP, 'W8'),

            Fixture::suite('C10', 'app-modules/health/src/Api/Queries/Fixtures/KeepsKeys.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Queries\Fixtures;

                use Modules\Kernel\Api\Findings;

                use function iterator_to_array;

                final readonly class KeepsKeys
                {
                    /** @return list<object> */
                    public function over(Findings $findings): array
                    {
                        return iterator_to_array($findings, preserve_keys: false);
                    }
                }
                PHP, 'C10 —', 'KeepsKeys.php'),

            // The same argument with the collection arriving as a call, which
            // is how a query hands over its own. It needs a fixture of its own
            // because it needs a different reading: a pattern that stops at the
            // first `)` matches the one above and walks past this one, and this
            // is the spelling the rule was written for.
            Fixture::suite('C10', 'app-modules/health/src/Api/Queries/Fixtures/KeepsKeysFromACall.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Queries\Fixtures;

                use Modules\Kernel\Api\Findings;

                use function iterator_to_array;

                final readonly class KeepsKeysFromACall
                {
                    /** @return list<object> */
                    public function over(Findings $findings): array
                    {
                        return iterator_to_array($this->worstFirst($findings), preserve_keys: false);
                    }

                    private function worstFirst(Findings $findings): Findings
                    {
                        return $findings;
                    }
                }
                PHP, 'C10 —', 'KeepsKeysFromACall.php'),
        ];
    }
}
