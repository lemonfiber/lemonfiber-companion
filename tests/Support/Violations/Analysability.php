<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** What keeps a class readable by the analyser. */
final readonly class Analysability
{
    /** @return list<Fixture> */
    public static function analysability(): array
    {
        return [
            Fixture::analyser('P1', 'Plain/MakesMembersUp.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class MakesMembersUp
                {
                    public function __get(string $name): mixed
                    {
                        return null;
                    }
                }
                PHP, 'P1 —'),

            Fixture::analyser('P2', 'Plain/ChoosesAtRuntime.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ChoosesAtRuntime
                {
                    public function make(string $class): object
                    {
                        return new $class();
                    }
                }
                PHP, 'P2 —'),

            // P2's other clause. The fixture above reaches a property by a name
            // held in a variable; this makes the *variable itself* dynamic,
            // which is the older and stranger half of the same sentence.
            Fixture::analyser('P2', 'Plain/NamesAVariable.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class NamesAVariable
                {
                    public function indirect(string $name): bool
                    {
                        $$name = true;

                        return $$name;
                    }
                }
                PHP, 'variable.dynamicName'),

            Fixture::analyser('Q3', 'Plain/BindsLate.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class BindsLate
                {
                    public function which(): string
                    {
                        return static::class;
                    }
                }
                PHP, 'Q3 —'),

            Fixture::analyser('Q4', 'Plain/WritesOutput.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WritesOutput
                {
                    public function say(): void
                    {
                        echo 'into the element tree';
                    }
                }
                PHP, 'Q4 —'),

            Fixture::analyser('C6', 'Plain/SwallowsEverything.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class SwallowsEverything
                {
                    public function attempt(): void
                    {
                        try {
                            $this->attempt();
                        } catch (\Throwable $caught) {
                        }
                    }
                }
                PHP, 'C6 —'),

            // C6's other clause. The fixture above catches broadly; this one
            // catches narrowly and does nothing, which is the half somebody
            // actually writes — an empty `catch` looks deliberate.
            Fixture::analyser('C6', 'Plain/CatchesAndSaysNothing.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use InvalidArgumentException;

                final class CatchesAndSaysNothing
                {
                    public function go(): bool
                    {
                        try {
                            throw new InvalidArgumentException('x');
                        } catch (InvalidArgumentException) {
                        }

                        return true;
                    }
                }
                PHP, 'lemonfiber.broadCatch'),

            Fixture::analyser('C7', 'Plain/AsksIfEmpty.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class AsksIfEmpty
                {
                    /** @param list<string> $findings */
                    public function healthy(array $findings): bool
                    {
                        return empty($findings);
                    }
                }
                PHP, 'C7 —'),

            Fixture::analyser('H8', 'Plain/LeavesByFourDoors.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class LeavesByFourDoors
                {
                    public function verdict(int $findings, bool $reachable): string
                    {
                        if (! $reachable) {
                            return 'unreachable';
                        }

                        if ($findings === 0) {
                            return 'healthy';
                        }

                        if ($findings === 1) {
                            return 'one finding';
                        }

                        return 'several findings';
                    }
                }
                PHP, 'H8 —'),

            Fixture::analyser('C9', 'Plain/NestsTernaries.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class NestsTernaries
                {
                    public function label(int $count): string
                    {
                        return $count > 9 ? ($count > 99 ? 'many' : 'some') : 'few';
                    }
                }
                PHP, 'C9 —'),

            // C9's other clause. The fixture above plants a nested ternary; the
            // rule also says "no `??` on an array subscript", which is a
            // different construct caught by a different check. A rule with two
            // clauses and one fixture has been shown to refuse half of itself.
            Fixture::analyser('C9', 'Plain/CoalescesOnASubscript.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class CoalescesOnASubscript
                {
                    /** @param array<string, string> $rows */
                    public function pick(array $rows): string
                    {
                        return $rows['missing'] ?? 'none';
                    }
                }
                PHP, 'lemonfiber.coalesceOnArray'),

            Fixture::analyser('P3', 'Plain/TakesAnything.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class TakesAnything
                {
                    public function all(): int
                    {
                        return count(func_get_args());
                    }
                }
                PHP, 'P3 —'),

            Fixture::analyser('P4', 'Plain/Reflects.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use ReflectionClass;

                final class Reflects
                {
                    public function name(): string
                    {
                        return new ReflectionClass(self::class)->getName();
                    }
                }
                PHP, 'P4 —'),

            Fixture::analyser('Q1', 'Plain/ChangesTheRuntime.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ChangesTheRuntime
                {
                    public function widen(): void
                    {
                        ini_set('memory_limit', '1G');
                    }
                }
                PHP, 'Q1 —'),

            // C8 is scoped to the domain by path, so its fixture sits at a path
            // shaped like one — the rule matches `/health/src/`, and the tree
            // this is written into supplies exactly that.
            Fixture::analyser('C8', 'health/src/GuardsANull.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Health;

                final readonly class GuardsANull
                {
                    public function reach(?self $other): mixed
                    {
                        return $other?->reach(null);
                    }
                }
                PHP, 'C8 —'),

            Fixture::analyser('Q2', 'Plain/ReadsSuperglobal.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsSuperglobal
                {
                    public function host(): mixed
                    {
                        return $_SERVER['HTTP_HOST'] ?? null;
                    }
                }
                PHP, 'Q2 —'),
        ];
    }
}
