<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Lemonfiber\Companion\PHPStan\Rules\NoManyMethodsRule;

use function sprintf;

use Tests\Support\Fixture;

/** What a surface says, and how much one class or method may hold. */
final readonly class SurfaceAndText
{
    /** @return list<Fixture> */
    public static function surfaceAndText(): array
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
