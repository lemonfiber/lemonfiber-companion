<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** How a failure is shaped, and where one module may reach into another. */
final readonly class ErrorsAndBoundaries
{
    /** @return list<Fixture> */
    public static function errorsAndShape(): array
    {
        return [
            Fixture::suite('C1', 'app-modules/health/src/Api/Fixtures/ReturnsNothing.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Fixtures;

                final readonly class ReturnsNothing
                {
                    public function repair(): void {}
                }
                PHP, 'C1 —'),

            Fixture::suite('C2', 'app-modules/health/src/Api/Fixtures/AnswersWithNull.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Fixtures;

                final readonly class AnswersWithNull
                {
                    public function verdict(): ?self
                    {
                        return null;
                    }
                }
                PHP, 'C2 —'),

            Fixture::analyser('C3', 'Plain/ThrowsBare.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ThrowsBare
                {
                    public function refuse(): never
                    {
                        throw new \RuntimeException('says nothing');
                    }
                }
                PHP, 'C3 —'),

            Fixture::analyser('C4', 'Plain/Silences.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class Silences
                {
                    public function peek(): mixed
                    {
                        return @constant('NOPE');
                    }
                }
                PHP, 'ergebnis.noErrorSuppression'),

            Fixture::analyser('C5', 'Plain/HasElse.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class HasElse
                {
                    public function pick(bool $yes): string
                    {
                        if ($yes) {
                            return 'a';
                        } else {
                            return 'b';
                        }
                    }
                }
                PHP, 'C5 —'),

            Fixture::analyser('C5', 'Plain/HasSwitch.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class HasSwitch
                {
                    public function pick(string $key): string
                    {
                        switch ($key) {
                            case 'a':
                                return 'a';
                            default:
                                return 'b';
                        }
                    }
                }
                PHP, 'ergebnis.noSwitch'),

            Fixture::suite('D1', 'app-modules/health/src/Api/Fixtures/TakesArray.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Fixtures;

                final readonly class TakesArray
                {
                    /** @param array<int, string> $options */
                    public function apply(array $options): self
                    {
                        return $this;
                    }
                }
                PHP, 'D1 —'),

            Fixture::suite('D2', 'app-modules/health/src/Api/Fixtures/TakesPrimitive.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Fixtures;

                final readonly class TakesPrimitive
                {
                    public function repair(string $findingId): self
                    {
                        return $this;
                    }
                }
                PHP, 'D2 —'),

            Fixture::analyser('D3', 'Plain/Untyped.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class Untyped
                {
                    public function pass($anything): string
                    {
                        return 'x';
                    }
                }
                PHP, 'missingType.parameter'),

            // The second half of D3, and the half its own fixture was not
            // proving. The one above plants a *missing* type and shows the
            // analyser refuses it. The rule says "no `mixed` in public
            // signatures", which is a different claim: `mixed` is a declared
            // type, so it is fully covered by the type-coverage measure and
            // legal at level max. The rule read as enforced and nothing checked
            // it.
            Fixture::suite('D3', 'app-modules/health/src/Api/Fixtures/SaysMixed.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Api\Fixtures;

                final readonly class SaysMixed
                {
                    public function anything(mixed $said): mixed
                    {
                        return $said;
                    }
                }
                PHP, 'D3 —', 'SaysMixed'),

            Fixture::suite('D4', 'app-modules/health/src/Fixtures/SchemeIsALiteral.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class SchemeIsALiteral
                {
                    // The rule quoted in prose: `$scheme === 'https'`. The check reads
                    // tokens, so this line must not be what fails it — the one below must.
                    public function isEncrypted(string $scheme): bool
                    {
                        return $scheme === 'https';
                    }

                    public function isBlank(string $said): bool
                    {
                        return $said === '';
                    }
                }
                PHP, 'D4 — a closed set'),

            Fixture::suite('D4', 'app-modules/health/src/Fixtures/RepairStatus.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class RepairStatus {}
                PHP, 'D4 — a closed set'),

            Fixture::suite('D4', 'app-modules/health/src/Fixtures/StandingIsAMatch.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class StandingIsAMatch
                {
                    // The same claim as `$said === 'granted'`, in the form this
                    // codebase actually writes it — and the form D4 could not see
                    // until the checker learned to read match arms. The subject's
                    // own parentheses close before the body opens, which is where
                    // the first attempt at reading them stopped.
                    public function means(string $said): int
                    {
                        return match ($said) {
                            'granted', 'provisional' => 1,
                            'denied' => 2,
                            default => 3,
                        };
                    }

                    // Bodies are answers, not vocabularies. This arm must not be
                    // what fails the fixture — a checker reading both sides would
                    // report every message in the codebase and get switched off.
                    public function describe(int $code): string
                    {
                        return match ($code) {
                            1 => 'it was allowed',
                            default => 'it was not',
                        };
                    }
                }
                PHP, 'D4 — a closed set', 'StandingIsAMatch'),

            Fixture::analyser('D4', 'Plain/MatchesDefault.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                enum Tone: string
                {
                    case Calm = 'calm';
                    case Loud = 'loud';
                }

                final class MatchesDefault
                {
                    public function describe(Tone $tone): string
                    {
                        return match ($tone) {
                            Tone::Calm => 'calm',
                            default => 'something else',
                        };
                    }
                }
                PHP, 'shipmonk.defaultMatchArmWithEnum'),

            Fixture::analyser('D5', 'Plain/PositionalBoolean.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class PositionalBoolean
                {
                    public function has(string $needle): bool
                    {
                        return in_array($needle, ['a', 'b'], true);
                    }
                }
                PHP, 'D5 —'),
        ];
    }

    /** @return list<Fixture> */
    public static function boundaries(): array
    {
        return [
            Fixture::suite('E1', 'app-modules/health/src/Fixtures/ReachesSurface.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                use Modules\Operator\Api\Fixtures\Reachable;

                final readonly class ReachesSurface
                {
                    public function surface(): Reachable
                    {
                        return new Reachable();
                    }
                }
                PHP, 'E1 — health', 'Modules\\Operator'),

            Fixture::suite('E2', 'app-modules/operator/src/Fixtures/ReachesInternals.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Fixtures;

                use Modules\Health\Internal\Fixtures\Hidden;

                final readonly class ReachesInternals
                {
                    public function hidden(): Hidden
                    {
                        return new Hidden();
                    }
                }
                PHP, 'E2 — health publishes', 'ReachesInternals'),

            Fixture::suite('E3', 'app-modules/stacks/src/Fixtures/NamesTheSdk.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Stacks\Fixtures;

                use Lemonfiber\Sdk\Client;

                final readonly class NamesTheSdk
                {
                    public function client(): Client
                    {
                        return new Client();
                    }
                }
                PHP, 'E3 — the SDK is named in exactly one module', 'Lemonfiber\\Sdk'),

            Fixture::suite('E4', 'app-modules/backups/src/Fixtures/NamesThePlatform.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Backups\Fixtures;

                use Native\Mobile\Edge\Element;

                final readonly class NamesThePlatform
                {
                    public function element(): ?Element
                    {
                        return null;
                    }
                }
                PHP, 'A7/E4 — backups', 'Native'),
        ];
    }
}
