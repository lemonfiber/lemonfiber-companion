<?php

declare(strict_types=1);

namespace Tests\Support;

use Lemonfiber\Companion\PHPStan\Rules\NoManyMethodsRule;

use function sprintf;

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
 *
 * Every path below sits under a directory called `Fixtures`, and that is load
 * bearing: `.gitignore` and `pint.json` both exclude that one name. The files
 * are genuinely on disk while a run is in progress, so without the exclusion a
 * commit made beside one picks up a deliberate rule violation and the formatter
 * fails on files whose purpose is to be wrong.
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
            ...self::placement(),
            ...self::templates(),
            ...self::floors(),
            ...self::rulesAboutRules(),
            ...self::whatASurfaceIsNeverShown(),
            ...self::drivenDirectly(),
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

            // The other declaration, and its own fixture rather than a second
            // assertion on the two above: a static inside a method body has no
            // property for reflection to find and survives a dispatch just as
            // completely, so a fixture proving the property half says nothing
            // at all about this one.
            Fixture::suite('A6', 'app-modules/health/src/Fixtures/HoldsBetweenCalls.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class HoldsBetweenCalls
                {
                    public function soFar(): int
                    {
                        static $seen = 0;

                        return ++$seen;
                    }
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

            // H3's second cap, and its own fixture rather than a second
            // assertion on the one above: the row names four caps and each has
            // a different mechanism, so a fixture proving one says nothing
            // about the others. This is the one a screen reaches by growing an
            // accessor per field.
            Fixture::analyser('H3', 'Plain/Crowded.php', self::crowdedClass(), 'declares 21 methods'),

            Fixture::suite('F2', 'app-modules/operator/src/Internal/Presenters/Fixtures/Presenter.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Presenters\Fixtures;

                use Modules\Kernel\Api\Fixtures\Asked;

                final readonly class Presenter
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

            // A screen that walks a run without naming `WorstFirst`, which is
            // the whole of the violation: the type is imported, the rows come
            // out in the order they went in, and nothing anywhere says so.
            Fixture::suite('F8', 'app-modules/operator/src/Internal/Screens/Fixtures/ShowsWhateverArrived.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens\Fixtures;

                use Modules\Kernel\Api\Findings;

                final readonly class ShowsWhateverArrived
                {
                    /** @return list<string> */
                    public function rows(Findings $findings): array
                    {
                        $rows = [];

                        foreach ($findings as $finding) {
                            $rows[] = $finding->title();
                        }

                        return $rows;
                    }
                }
                PHP, 'F8 —', 'ShowsWhateverArrived'),

            // A screen that names a template nobody wrote. `F10` makes the join
            // between markup and class from `render()`, so a screen whose view
            // is not there is a screen whose markup was never read at all —
            // which is the rule reporting rather than the rule passing.
            //
            // Its other branch, a template calling a method the screen lost,
            // wants a `.blade.php` on disk. One written here would be picked up
            // by `Template::all()` and judged by the nine other rules over
            // `resources/views`, so proving this rule would mean satisfying all
            // of them first — a fixture whose failure could be any of ten
            // things is not evidence about one of them.
            Fixture::suite('F10', 'app-modules/operator/src/Internal/Screens/Fixtures/RendersATemplateNobodyWrote.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens\Fixtures;

                use Illuminate\View\View;

                use function view;

                final readonly class RendersATemplateNobodyWrote
                {
                    public function render(): View
                    {
                        return view('operator::a-template-nobody-wrote');
                    }
                }
                PHP, 'F10 —', 'RendersATemplateNobodyWrote'),

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

            Fixture::analyser('H5', 'Plain/JoinsTwoLiterals.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class JoinsTwoLiterals
                {
                    public function said(): string
                    {
                        return 'This stack is not answering, '
                            . 'and nothing here can say why.';
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

            Fixture::suite('L2', 'lang/en/Fixtures/planted.php', <<<'PHP'
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

            Fixture::suite('G3', 'tests/Feature/Fixtures/G3Test.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Illuminate\Support\Facades\Http;

                it('G3 — a test that reaches the network is stopped', function (): void {
                    Http::get('https://example.test/');
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

            Fixture::suite('N1-R39', 'app-modules/operator/src/Internal/Screens/ShowsSomebodysStack.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens;

                use Illuminate\View\View;
                use Modules\Kernel\Api\Reading;
                use Native\Mobile\Edge\NativeComponent;

                final class ShowsSomebodysStack extends NativeComponent
                {
                    public function __construct(private Reading $reading) {}

                    public function render(): View
                    {
                        return view('operator::your-stacks');
                    }
                }
                PHP, 'N1-R39 —', 'ShowsSomebodysStack'),

            Fixture::suite('N4-R18', 'app-modules/operator/src/Internal/Screens/ShowsASessionOpenly.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens;

                use Illuminate\View\View;
                use Modules\Kernel\Api\Session;
                use Native\Mobile\Edge\NativeComponent;

                final class ShowsASessionOpenly extends NativeComponent
                {
                    public function __construct(private Session $session) {}

                    public function render(): View
                    {
                        return view('operator::your-stacks');
                    }
                }
                PHP, 'N4-R18 —', 'ShowsASessionOpenly'),

            Fixture::analyser('N4-R11', 'Plain/RaisesItsOwnAlert.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Native\Mobile\Facades\Dialog;

                final class RaisesItsOwnAlert
                {
                    public function tell(): void
                    {
                        Dialog::alert('Heads up', 'Something happened on your stack.');
                    }
                }
                PHP, 'N4-R11 —'),

            Fixture::analyser('N1-R21', 'Plain/TurnsVerificationOff.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Http\Client\Factory;

                final class TurnsVerificationOff
                {
                    public function __construct(private Factory $http) {}

                    public function call(): void
                    {
                        $this->http->withoutVerifying()->get('https://example.test');
                    }
                }
                PHP, 'N1-R21 —'),

            Fixture::analyser('N1-R16', 'Plain/OpensItsOwnConnection.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use CurlHandle;

                final class OpensItsOwnConnection
                {
                    public function byCurl(CurlHandle $handle): void
                    {
                        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, named: false);
                    }
                }
                PHP, 'N1-R16 —'),

            Fixture::suite('N1-R21', '.env.fixtureplanted', <<<'ENV'
                LEMONFIBER_VERIFY_TLS=true
                ENV, 'N1-R21 —', 'lemonfiber_verify_tls'),

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

            // The literal is the easy half. A verification flag read from
            // configuration arrives as a string or through a variable, and the
            // rule used to read the node rather than ask the analyser — so
            // `'0'`, `''`, `null` and anything one line away all passed.
            Fixture::analyser('S3', 'Plain/WeakensTlsFromAVariable.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WeakensTlsFromAVariable
                {
                    /** @return array<string, mixed> */
                    public function options(): array
                    {
                        $verify = '0';

                        return ['verify' => $verify, 'timeout' => 5];
                    }
                }
                PHP, 'S3 —'),

            // The polarity half. `verify_expiry` asks for the expiry to be
            // checked, so `false` is the spelling that waives it — and while
            // this name sat in the off-when-true list the analyser refused
            // `true` and passed exactly this.
            Fixture::analyser('S3', 'Plain/WaivesTheExpiryCheck.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WaivesTheExpiryCheck
                {
                    /** @return array<string, mixed> */
                    public function options(): array
                    {
                        return ['verify_expiry' => false, 'timeout' => 5];
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
    private static function placement(): array
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

            Fixture::suite('W5', 'app-modules/health/tests/Fixtures/SaysNothingTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Closure;

                it('imports a name into the namespace it is already in', function (): void {
                    expect(Closure::class)->toBe('Closure');
                });
                PHP, 'W5 —', 'SaysNothingTest'),

            // A source file, not a test: C10 exempts tests deliberately, so a
            // fixture planted under `tests/` would prove the rule green while
            // refusing nothing.
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

    /**
     * The Blade rules.
     *
     * Written as templates in a module's own view directory, because that is
     * where the suite looks and because a `.blade.php` file is not a class —
     * nothing that reflects over namespaces can see one, which is the whole
     * reason these checks exist separately from the architecture rules.
     *
     * @return list<Fixture>
     */
    private static function templates(): array
    {
        $views = 'app-modules/operator/resources/views/Fixtures';

        return [
            Fixture::suite('F3', sprintf('%s/unknown-class.blade.php', $views), <<<'BLADE'
                <native:column class="w-full flex-nonsense">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'every class in', 'unknown-class'),

            Fixture::suite('F3', sprintf('%s/unknown-tag.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:nonsense-tag />
                </native:column>
                BLADE, 'every tag in', 'unknown-tag'),

            Fixture::suite('F3', sprintf('%s/bare-tag.blade.php', $views), <<<'BLADE'
                <column class="w-full">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </column>
                BLADE, 'every element in', 'bare-tag'),

            Fixture::suite('F3', sprintf('%s/holds-logic.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    @php $count = 1; @endphp
                </native:column>
                BLADE, 'holds no logic', 'holds-logic'),

            Fixture::suite('F3', sprintf('%s/opens-a-web-view.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:webview src="https://example.test" />
                </native:column>
                BLADE, 'opens no web view', 'opens-a-web-view'),

            Fixture::suite('F3', sprintf('%s/literal-colour.blade.php', $views), <<<'BLADE'
                <native:column class="w-full bg-red-500">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'names no literal colour', 'literal-colour'),

            // The one every other fixture here is invisible to. A class token
            // holding an echo is dropped unread, so the three rules above go
            // quiet together: this file names an unknown utility, a literal
            // colour and the accent set as text, and F3, DES-R24 and DES-R15
            // all pass it.
            Fixture::suite('F9', sprintf('%s/runtime-class.blade.php', $views), <<<'BLADE'
                <native:column class="{{ $open ? 'bg-theme-accnt' : 'bg-red-500' }}">
                    <native:text class="{{ $open ? 'text-theme-accent' : '' }}">{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'F9 —', 'runtime-class'),

            Fixture::suite('F5', sprintf('%s/silent-control.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:fab icon="plus" />
                </native:column>
                BLADE, 'every control in', 'silent-control'),

            Fixture::suite('F6', sprintf('%s/no-empty-state.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    @foreach ($findings as $finding)
                        <native:text>{{ $finding->title }}</native:text>
                    @endforeach
                </native:column>
                BLADE, 'every list in', 'no-empty-state'),

            Fixture::suite('L1', sprintf('%s/english-sentence.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:text>This stack cannot be reached from here.</native:text>
                </native:column>
                BLADE, 'reads its text from the translator', 'english-sentence'),
        ];
    }

    /**
     * The per-module floors.
     *
     * The clover fixture goes to the path the real report uses. `coverage/` is
     * generated output and wholly ignored by git already, so it needs no
     * `Fixtures` directory to stay invisible — and a report written by hand is
     * only portable because the reader strips the repository root, which an
     * absolute path from CI would otherwise carry.
     *
     * @return list<Fixture>
     */
    private static function floors(): array
    {
        return [
            Fixture::suite('G7', 'app-modules/Fixtures/composer.json', <<<'JSON'
                {
                    "name": "modules/fixtures",
                    "description": "A module that states no bar of its own.",
                    "type": "library",
                    "license": "LicenseRef-Hippocratic-3.0",
                    "require": { "php": "^8.5" },
                    "extra": { "lemonfiber": { "kind": "capability" } }
                }
                JSON, 'G7 — every module declares', 'fixtures'),

            Fixture::suite('G9', 'coverage/clover.xml', <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <coverage generated="0">
                  <project timestamp="0" name="Clover Coverage">
                    <package name="Modules\Health">
                      <file name="app-modules/health/src/Fixtures/Bare.php">
                        <metrics loc="9" ncloc="9" classes="1" methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="4" coveredstatements="1" elements="5" coveredelements="1"/>
                      </file>
                    </package>
                  </project>
                </coverage>
                XML, 'G9 — every module meets', 'health is at 25.0%'),
        ];
    }

    /** @return list<Fixture> */
    private static function rulesAboutRules(): array
    {
        return [
            Fixture::suite('R1', 'tests/Arch/Fixtures/UndocumentedRuleTest.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                // Z9 — an identifier no rule table mentions.

                it('carries an identifier the document does not list', function (): void {
                    expect(true)->toBeTrue();
                });
                PHP, 'documents every rule the codebase enforces', 'Z9'),

            Fixture::suite('R3', 'tests/Arch/Fixtures/MalformedRuleTest.php', <<<'PHP'
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
                PHP, 'R3 — every namespace an expectation names resolves', 'MalformedRuleTest'),
        ];
    }

    /**
     * The requirements a surface is held to by what it may not name.
     *
     * Each of these was driven by hand when it was written, which proves it once
     * and on one machine. Here it is proved on every run, which is what the
     * harness is for.

     *
     * @return list<Fixture>
     */
    private static function whatASurfaceIsNeverShown(): array
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

            Fixture::suite('N3-R8', 'native/resources/android/PlaysMedia.kt', <<<'KOTLIN'
                package app.lemonfiber.native

                class PlaysMedia(private val context: Context) {
                    fun play(url: String) {
                        val player = ExoPlayer.Builder(context).build()
                    }
                }
                KOTLIN, 'N3-R8 — no platform source reaches for a media player'),

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
                "{{ __('connection.setup_is_at_the_machine') }}",
                "{{ __('connection.setup_happens_at_the_machine') }}",
                'L7 —',
                'connection.setup_happens_at_the_machine',
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
    private static function drivenDirectly(): array
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
    private static function notDrivable(): array
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

    /**
     * A class with one method more than the cap allows.
     *
     * Built rather than written out, so the count and the cap cannot drift
     * apart in a file somebody edits — the number here is the rule's own plus
     * one, and a reader can see that it is.
     */
    private static function crowdedClass(): string
    {
        $methods = '';

        for ($answer = 1; $answer <= NoManyMethodsRule::THE_MOST_METHODS + 1; $answer++) {
            $methods .= sprintf("\n    public function answer%d(): int\n    {\n        return %d;\n    }\n", $answer, $answer);
        }

        return sprintf(
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Fixtures\\Plain;\n\nfinal class Crowded\n{%s}\n",
            $methods,
        );
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
