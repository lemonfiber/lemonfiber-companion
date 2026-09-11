<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * One violation per rule, and the mark it must leave.
 *
 * ARCHITECTURE.md stays the index. This adds the third leg: the rule is
 * documented, an artifact carries its identifier, and the artifact demonstrably
 * refuses a violation. Without the third, a rule that silently checks nothing
 * satisfies the first two — a namespace no autoloader registers, an `expect()`
 * list that voids itself, a Pest expectation that does not report what its name
 * says it reports. Each of those reads as a green tick.
 *
 * Analyser fixtures land in a tree outside every `allowIn` path, so a rule
 * scoped to "everywhere but the adapters" applies to them. Suite fixtures land
 * inside real modules, because a module namespace is the only thing a Pest
 * architecture expectation can resolve.
 */
final readonly class Fixtures
{
    /** Where analyser fixtures are written; outside every allowIn path. */
    public const string ANALYSER_TREE = '.rule-fixtures';

    /**
     * Files a fixture needs in order to compile, which are not themselves the
     * violation.
     *
     * A boundary fixture has to name something on the other side of the
     * boundary, and a presenter fixture has to be handed a real interface. These
     * are written and removed with the fixtures and break no rule of their own.
     *
     * @return array<string, string> path => code
     */
    public static function companions(): array
    {
        return [
            'app-modules/kernel/src/Api/Fixtures/Asked.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Kernel\Api\Fixtures;

                interface Asked
                {
                    public function answer(): string;
                }
                PHP,
            'app-modules/operator/src/Api/Fixtures/Reachable.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Api\Fixtures;

                final readonly class Reachable {}
                PHP,
            'app-modules/health/src/Internal/Fixtures/Hidden.php' => <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal\Fixtures;

                final readonly class Hidden {}
                PHP,
        ];
    }

    /** @return list<Fixture> */
    public static function all(): array
    {
        return [
            ...self::frameworkCoupling(),
            ...self::primitives(),
            ...self::errorsAndShape(),
            ...self::boundaries(),
            ...self::surfaceAndText(),
            ...self::testsAndNaming(),
            ...self::analysability(),
            ...self::textAndSecurity(),
            ...self::sizeAndSuiteIntegrity(),
            ...self::rulesAboutRules(),
            ...self::notDrivable(),
        ];
    }

    /** @return list<Fixture> */
    private static function frameworkCoupling(): array
    {
        return [
            Fixture::suite('A1', 'app-modules/health/src/Fixtures/UsesEloquent.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                use Illuminate\Database\Eloquent\Model;

                final class UsesEloquent extends Model {}
                PHP, 'A1 — no Illuminate\\Database\\Eloquent', 'Modules\\Health'),

            Fixture::analyser('A2', 'Plain/UsesFacade.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Support\Facades\Cache;

                final class UsesFacade
                {
                    public function driver(): string
                    {
                        return Cache::getDefaultDriver();
                    }
                }
                PHP, 'A2 —'),

            Fixture::analyser('A3', 'Plain/LocatesService.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class LocatesService
                {
                    public function reach(): mixed
                    {
                        return app('something');
                    }
                }
                PHP, 'A3/A4'),

            Fixture::analyser('A4', 'Plain/ReadsConfig.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsConfig
                {
                    public function name(): mixed
                    {
                        return config('app.name');
                    }
                }
                PHP, 'A3/A4'),

            Fixture::suite('A5', 'app-modules/health/src/Fixtures/ReadsEnvironment.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class ReadsEnvironment
                {
                    public function debug(): mixed
                    {
                        return env('APP_DEBUG');
                    }
                }
                PHP, 'A5 — configuration is read from config'),

            Fixture::suite('A6', 'app-modules/health/src/Fixtures/HoldsStatic.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final class HoldsStatic
                {
                    public static string $cached = 'survives a dispatch';
                }
                PHP, 'A6/I1'),

            Fixture::suite('I1', 'app-modules/health/src/Fixtures/HoldsAnotherStatic.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final class HoldsAnotherStatic
                {
                    public static int $count = 0;
                }
                PHP, 'A6/I1'),

            Fixture::suite('A7', 'app-modules/health/src/Fixtures/UsesIlluminate.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                use Illuminate\Support\Collection;

                final readonly class UsesIlluminate
                {
                    public function holds(): Collection
                    {
                        return new Collection();
                    }
                }
                PHP, 'A7/E4 — health', 'Illuminate'),

            Fixture::analyser('A8', 'Plain/UsesPlatformFacade.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Native\Mobile\Facades\SecureStorage;

                final class UsesPlatformFacade
                {
                    public function token(): mixed
                    {
                        return SecureStorage::get('session');
                    }
                }
                PHP, 'A8 —'),

            Fixture::analyser('A9', 'Plain/WorkingProvider.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Support\ServiceProvider;

                final class WorkingProvider extends ServiceProvider
                {
                    public function boot(): void
                    {
                        $warm = $this->app->make('something');
                    }
                }
                PHP, 'A9 —'),
        ];
    }

    /** @return list<Fixture> */
    private static function primitives(): array
    {
        return [
            Fixture::analyser('B1', 'Plain/ReadsClock.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsClock
                {
                    public function stamp(): int
                    {
                        return time();
                    }
                }
                PHP, 'B1 —'),

            Fixture::analyser('B2', 'Plain/RollsDice.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class RollsDice
                {
                    public function pick(): int
                    {
                        return random_int(1, 6);
                    }
                }
                PHP, 'B2 —'),

            Fixture::analyser('B3', 'Plain/ReadsDisk.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsDisk
                {
                    public function contents(): string|false
                    {
                        return file_get_contents('/etc/hostname');
                    }
                }
                PHP, 'B3 —'),

            Fixture::analyser('B4', 'Plain/Waits.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class Waits
                {
                    public function hold(): void
                    {
                        sleep(1);
                    }
                }
                PHP, 'B4 —'),
        ];
    }

    /** @return list<Fixture> */
    private static function errorsAndShape(): array
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

            Fixture::suite('D4', 'app-modules/health/src/Fixtures/RepairStatus.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class RepairStatus {}
                PHP, 'D4 — a closed set'),

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
    private static function boundaries(): array
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

    /** @return list<Fixture> */
    private static function surfaceAndText(): array
    {
        return [
            Fixture::analyser('F1', 'Plain/Tangled.php', self::tangledMethod(), 'Cognitive complexity'),
            Fixture::analyser('H3', 'Plain/AlsoTangled.php', self::tangledMethod(), 'Cognitive complexity'),

            Fixture::suite('F2', 'app-modules/operator/src/Internal/Presenters/FixturePresenter.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Presenters;

                use Modules\Kernel\Api\Fixtures\Asked;

                final readonly class FixturePresenter
                {
                    public function __construct(private Asked $asked) {}

                    public function present(): string
                    {
                        return $this->asked->answer();
                    }
                }
                PHP, 'F2 —'),

            Fixture::suite('F4', 'app-modules/operator/src/Fixtures/EagerScreen.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Fixtures;

                use Modules\Kernel\Api\Fixtures\Asked;
                use Native\Mobile\Edge\NativeComponent;

                final class EagerScreen extends NativeComponent
                {
                    public function __construct(private readonly Asked $asked) {}
                }
                PHP, 'F4 —'),

            Fixture::analyser('H5', 'Plain/Concatenates.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class Concatenates
                {
                    public function label(string $name): string
                    {
                        return 'Stack ' . $name;
                    }
                }
                PHP, 'H5 —'),

            Fixture::analyser('H5', 'Plain/Interpolates.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class Interpolates
                {
                    public function label(string $name): string
                    {
                        return "Stack {$name} is asleep";
                    }
                }
                PHP, 'H5 —'),

            Fixture::analyser('L1', 'Presenters/SpeaksEnglish.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Presenters;

                final class SpeaksEnglish
                {
                    public function headline(): string
                    {
                        return 'This stack cannot be reached from here.';
                    }
                }
                PHP, 'L1 —'),

            Fixture::suite('L2', 'lang/en/fixture.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                return [
                    'only_in_english' => 'A key with no Dutch counterpart',
                ];
                PHP, 'L2 — the locales carry the same keys'),
        ];
    }

    /** @return list<Fixture> */
    private static function testsAndNaming(): array
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

            Fixture::suite('G3', 'tests/Feature/FixtureG3Test.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Illuminate\Support\Facades\Http;

                it('G3 — a test that reaches the network is stopped', function (): void {
                    Http::get('https://example.test/');
                });
                PHP, 'G3 —'),

            Fixture::suite('G5', 'app-modules/health/tests/Fixtures/AssertsTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                it('uses the other idiom', function (): void {
                    $this->assertTrue(true);
                });
                PHP, 'G5 —'),

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

    /** @return list<Fixture> */
    private static function analysability(): array
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

    /** @return list<Fixture> */
    private static function textAndSecurity(): array
    {
        return [
            Fixture::analyser('L3', 'Plain/CountsBytes.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class CountsBytes
                {
                    public function width(string $name): int
                    {
                        return strlen($name);
                    }
                }
                PHP, 'L3 —'),

            Fixture::analyser('L4', 'Plain/FormatsADate.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class FormatsADate
                {
                    public function shown(int $stamp): string
                    {
                        return gmdate('d/m/Y', $stamp);
                    }
                }
                PHP, 'L4 —'),

            Fixture::analyser('L5', 'Plain/FormatsANumber.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class FormatsANumber
                {
                    public function shown(float $size): string
                    {
                        return number_format($size, 2, '.', ',');
                    }
                }
                PHP, 'L5 —'),

            Fixture::analyser('L6', 'Plain/SortsByBytes.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class SortsByBytes
                {
                    public function order(string $a, string $b): int
                    {
                        return strcmp($a, $b);
                    }
                }
                PHP, 'L6 —'),

            Fixture::analyser('S1', 'Plain/RunsAProgram.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class RunsAProgram
                {
                    public function out(): mixed
                    {
                        return shell_exec('echo hello');
                    }
                }
                PHP, 'S1 —'),

            Fixture::analyser('S3', 'Plain/WeakensTls.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WeakensTls
                {
                    /** @return array<string, mixed> */
                    public function options(): array
                    {
                        return ['verify' => false, 'timeout' => 5];
                    }
                }
                PHP, 'S3 —'),
        ];
    }

    /** @return list<Fixture> */
    private static function sizeAndSuiteIntegrity(): array
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
        ];
    }

    /** @return list<Fixture> */
    private static function rulesAboutRules(): array
    {
        return [
            Fixture::suite('R1', 'tests/Arch/FixtureUndocumentedRuleTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                // Z9 — an identifier no rule table mentions.

                it('carries an identifier the document does not list', function (): void {
                    expect(true)->toBeTrue();
                });
                PHP, 'documents every rule the codebase enforces', 'Z9'),

            Fixture::suite('R3', 'tests/Arch/FixtureMalformedRuleTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                arch('a list on the left of toBeUsedIn')
                    ->expect(['Illuminate\Database\Eloquent', 'Illuminate\Database\Query'])
                    ->not->toBeUsedIn(['App']);

                arch('a function name beside a namespace')
                    ->expect(['app', 'Illuminate\Support\Facades'])
                    ->not->toBeUsedIn(['App']);

                arch('a namespace nothing registers')
                    ->expect('Native')
                    ->not->toBeUsed();
                PHP, 'R3 — every namespace an expectation names resolves', 'FixtureMalformedRuleTest'),
        ];
    }

    /**
     * The rules no snippet can break, each with the reason.
     *
     * @return list<Fixture>
     */
    private static function notDrivable(): array
    {
        return [
            Fixture::notDrivable(
                'R2',
                'A fixture for this harness would have to be a documented rule with no '
                . 'fixture, which means editing ARCHITECTURE.md in place rather than '
                . 'dropping a file — and the harness writes whole files. Its own failure '
                . 'mode is the one it cannot plant.',
            ),
            Fixture::notDrivable(
                'E5',
                'A listener has to be registered before the dispatcher holds it, and the '
                . 'only place that happens is the composition root — which is a file the '
                . 'harness would have to edit and restore rather than a fixture it can drop '
                . 'in and delete. Driven by hand instead: a capability listener bound to a '
                . 'surface event, registered in AppServiceProvider::boot().',
            ),
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

    /** A method with enough branches to pass the cognitive complexity cap. */
    private static function tangledMethod(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Fixtures\Plain;

            final class Tangled
            {
                public function decide(int $a, int $b, int $c): int
                {
                    if ($a > 0) {
                        if ($b > 0) {
                            if ($c > 0) {
                                foreach ([$a, $b, $c] as $each) {
                                    if ($each > 10) {
                                        while ($each > 0) {
                                            $each--;

                                            if ($each === 5) {
                                                return $each;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    return 0;
                }
            }
            PHP;
    }
}
