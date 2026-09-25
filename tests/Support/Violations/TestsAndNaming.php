<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** What a test may be, what a comment may say, and what a thing is called. */
final readonly class TestsAndNaming
{
    /** @return list<Fixture> */
    public static function testsAndNaming(): array
    {
        return [
            Fixture::suite('G1', 'app-modules/health/tests/Fixtures/MocksTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('stands in for a type we do not own', function (): void {
                    $stack = $this->createMock(Throwable::class);

                    expect($stack)->toBeObject();
                });
                PHP, 'G1 — nothing mocks a type we do not own', 'MocksTest'),

            Fixture::suite('G3', 'tests/Feature/Fixtures/G3Test.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Illuminate\Support\Facades\Http;

                it('G3 — a test that reaches the network is stopped', function (): void {
                    Http::get('https://example.test/');
                });
                PHP, 'G3 —'),

            // The same rule in the tree it did not reach. The guard was bound
            // to four of the eight suites and `app-modules/*\/tests` was not
            // one of them — which is where every adapter that speaks to a stack
            // is tested, and where an unmocked read raised a refused
            // connection, which the SDK raises as `Unreachable`, rather than
            // Saloon's `NoMockResponse`: the difference between having dialled
            // and having been stopped. The
            // fixture is under a module's tests for exactly that reason (R4).
            Fixture::suite('G3', 'app-modules/sdk/tests/Fixtures/ReachesAStackTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Modules\Kernel\Api\Address;
                use Modules\Kernel\Api\Fingerprint;
                use Modules\Kernel\Api\Nonce;
                use Modules\Kernel\Api\Session;
                use Modules\Kernel\Api\Stack;
                use Modules\Kernel\Api\StackId;
                use Modules\Kernel\Api\StackName;
                use Modules\Sdk\Api\PinnedClients;

                it('G3 — a module test that reaches a stack is stopped', function (): void {
                    new PinnedClients()
                        ->client(
                            Stack::of(
                                StackId::of(Nonce::of(str_repeat('9', Nonce::SHORTEST))),
                                StackName::of('Nowhere'),
                                Address::of('https://127.0.0.1:1'),
                                Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
                            ),
                            Session::of('a-session-not-a-secret'),
                        )
                        ->read('/api/status');
                });
                PHP, 'G3 —'),

            // Planted under `tests/Support`, which `Tree::testFiles()` reads and no
            // testsuite loads. That is the whole trick and it is not incidental: a
            // real G10 violation is a fatal at load, so a fixture phpunit would
            // load could not be planted at all — the run would die before any
            // rule could report on it, taking every other fixture in the pass with
            // it. The rule reads text for the same reason.
            Fixture::suite('G10', 'tests/Support/Fixtures/SecondTestSourcesTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                // `testSources` is already declared, globally, in
                // tests/Arch/TestConventionsTest.php.
                function testSources(): array
                {
                    return [];
                }
                PHP, 'G10 —'),

            Fixture::suite('G5', 'app-modules/health/tests/Fixtures/AssertsTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('uses the other idiom', function (): void {
                    $this->assertTrue(true);
                });
                PHP, 'G5 —'),

            // Planted under `tests/Support`, which `Tree::testFiles()` reads and
            // no testsuite loads — `G10`'s trick, for a different reason. A real
            // G12 violation is an ordinary suite that passes: it stands a
            // payload in for a stack and never asks whether a stack could send
            // it. Planted anywhere a testsuite collects, it would run green
            // beside the rule reporting it and prove nothing about either.
            Fixture::suite('G12', 'tests/Support/Fixtures/StandsInForAStackTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('takes a stack at a payload nothing read against the contract', function (): void {
                    $body = ['api_version' => 1, 'kind' => 'doctor', 'data' => ['overall' => 'healthy']];

                    expect($body['kind'])->toBe('doctor');
                });
                PHP, 'G12 —'),

            // The other way a body is written, and the half the rule could not
            // see for as long as it read one mark. This one spells no field of
            // an envelope anywhere: the version and the kind are handed to the
            // envelope type as arguments, which is how every SDK reader suite
            // builds its payload. A fixture for the first half alone would go
            // on passing whatever this half did.
            Fixture::suite('G12', 'tests/Support/Fixtures/StandsInPositionallyTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Lemonfiber\Sdk\Envelope\Envelope;

                it('hands an envelope a payload nothing read against the contract', function (): void {
                    expect(new Envelope(1, 'doctor', ['overall' => 'healthy'])->kind)->toBe('doctor');
                });
                PHP, 'G12 —'),

            // The envelope type standing in as a carrier for a value a test
            // wants out of a closure. There is no contract kind here at all, so
            // nothing resolves, nothing is judged, and the suite is green about
            // a conversation neither end could have had. The rule has to name
            // it rather than pass over it, which is what this plants.
            Fixture::suite('G12', 'tests/Support/Fixtures/StandsInUnderNoKindTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Lemonfiber\Sdk\Envelope\Envelope;

                it('carries a value out of a closure in an envelope', function (): void {
                    expect(new Envelope(1, 'x', 'what one arm said')->data)->toBe('what one arm said');
                });
                PHP, 'G12 —'),

            Fixture::suite('H1', 'app-modules/health/src/Fixtures/RepairManager.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class RepairManager {}
                PHP, 'H1 — no class is named Manager'),

            Fixture::suite('H2', 'app-modules/health/src/Fixtures/RepairInterface.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                interface RepairInterface
                {
                    public function run(): void;
                }
                PHP, 'H2 — an interface is named'),

            Fixture::suite('H4', 'app-modules/health/tests/Fixtures/GhostTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('describes something that is not there', function (): void {
                    expect(true)->toBeTrue();
                });
                PHP, 'H4 —'),

            Fixture::suite('H6', 'app-modules/health/src/Fixtures/StackUnreachableException.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class StackUnreachableException {}
                PHP, 'H6 —'),

            Fixture::suite('K1', 'app-modules/health/src/Fixtures/Narrates.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class Narrates
                {
                    // Previously this read the file directly.
                    public function label(): string
                    {
                        return 'x';
                    }
                }
                PHP, 'K1 —'),

            // The same marker, at the end of a line of code. Its own fixture
            // because it is its own reading: a scan anchored at the start of a
            // line sees the one above and not this one, and this is where a
            // note left behind is actually written.
            Fixture::suite('K1', 'app-modules/health/src/Fixtures/NarratesInPassing.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class NarratesInPassing
                {
                    public function label(): string
                    {
                        return 'x';  // Previously this read the file directly.
                    }
                }
                PHP, 'K1 —'),

            Fixture::suite('K2', 'app-modules/health/src/Fixtures/Restates.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class Restates
                {
                    /**
                     * @return string
                     */
                    public function label(): string
                    {
                        return 'x';
                    }
                }
                PHP, 'K2 —'),

            Fixture::suite('K3', 'app-modules/health/src/Fixtures/Repeats.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                /**
                 * A label, for a screen that has to call this something.
                 *
                 * `InvalidArgumentException` rather than `RuntimeException`, which is
                 * what every other refusal here extends and is not merely convention:
                 * the analyser treats a `RuntimeException` as checked, and every Pest
                 * test body is a closure, so the runtime kind would make this refusal
                 * the one case that no test anywhere could exercise.
                 *
                 * rather than , which is what every other refusal here extends and is
                 * not merely convention: the analyser treats a as checked, and every
                 * Pest test body is a closure, so the runtime kind would make this
                 * refusal the one case that no test anywhere could exercise.
                 */
                final readonly class Repeats
                {
                    public function label(): string
                    {
                        return 'x';
                    }
                }
                PHP, 'K3 —'),

            Fixture::suite('K4', 'app-modules/health/src/Fixtures/Orphans.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class Orphans
                {
                    /**
                     * What a screen calls this, for the one place that has to name it.
                     */
                    /**
                     * How many of them there are.
                     */
                    public function howMany(): int
                    {
                        return 1;
                    }
                }
                PHP, 'K4 —'),

            Fixture::suite('M1', 'app-modules/health/src/Api/Commands/Fixtures/AnswersPlainly.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Commands\Fixtures;

                final readonly class AnswersPlainly
                {
                    public function __invoke(): string
                    {
                        return 'done';
                    }
                }
                PHP, 'M1 —'),

            // M1's other clause. The fixture above is a command answering with
            // something that is not an `Outcome`; this is a query answering
            // with one — the same rule read from the other end. A question that
            // reports whether it worked is a question that did something.
            Fixture::suite('M1', 'app-modules/health/src/Api/Queries/Fixtures/AnswersWithOutcome.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Queries\Fixtures;

                use Modules\Kernel\Api\IdempotencyKey;
                use Modules\Kernel\Api\Outcome;

                final readonly class AnswersWithOutcome
                {
                    public function __invoke(): Outcome
                    {
                        return Outcome::of(IdempotencyKey::of('a-key'));
                    }
                }
                PHP, 'M1 —', 'AnswersWithOutcome'),

            Fixture::suite('M2', 'app-modules/health/src/Api/Commands/Fixtures/DoesTwoThings.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Commands\Fixtures;

                final readonly class DoesTwoThings
                {
                    public function __invoke(): self
                    {
                        return $this;
                    }

                    public function describe(): self
                    {
                        return $this;
                    }
                }
                PHP, 'M2 —'),

            Fixture::suite('M3', 'app-modules/health/src/Api/Commands/Fixtures/CannotBeRetried.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Commands\Fixtures;

                final readonly class CannotBeRetried
                {
                    public function __construct(private string $stack) {}

                    public function __invoke(): self
                    {
                        return $this;
                    }
                }
                PHP, 'M3 —'),
        ];
    }
}
