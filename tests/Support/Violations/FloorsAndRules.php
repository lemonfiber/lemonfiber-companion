<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** The coverage floors, and the rules about the rules. */
final readonly class FloorsAndRules
{
    /**
     * The per-tree floors.
     *
     * Both `G7` fixtures are edits to files this repository owns rather than
     * files dropped in beside them, and that is forced by what the rule reads:
     * the trees come from `phpunit.xml` and the floors from the manifest
     * nearest each one, so the smallest violation of either is a change to one
     * of those two files. A planted manifest under a directory nothing
     * measures would be read by nothing at all.
     *
     * The clover fixture goes to the path the real report uses. A report
     * written by hand is only portable because the reader strips the
     * repository root, which an absolute path from CI would otherwise carry.
     *
     * @return list<Fixture>
     */
    public static function floors(): array
    {
        return [
            // A measured tree whose nearest manifest states no bar. The floors
            // block is left in place and emptied rather than removed, because
            // the failure worth catching is the one that looks like it was
            // filled in — a manifest with no block at all is the state a new
            // package arrives in and the state somebody is looking for.
            Fixture::edit(
                'G7',
                'bridge/composer.json',
                <<<'JSON'
                        "lemonfiber": {
                            "floors": {
                                "coverage": 100,
                                "mutation": 100
                            }
                        },
                JSON,
                <<<'JSON'
                        "lemonfiber": {
                            "floors": {}
                        },
                JSON,
                'G7 — every measured tree is held',
                'bridge/src',
            ),

            // A second tree falling back to the root's manifest, which is how
            // one pair of numbers comes to hold two trees. Measuring
            // `database` is the realistic way in: it is a directory this
            // repository has, it holds PHP, and nothing between it and the
            // root declares a floor — so it lands on `bootstrap/Composition`'s
            // without either tree saying it is sharing.
            Fixture::edit(
                'G7',
                'phpunit.xml',
                '            <directory>bootstrap/Composition</directory>',
                "            <directory>bootstrap/Composition</directory>\n            <directory>database</directory>",
                'G7 — no manifest is nearest',
                'database',
            ),

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
                XML, 'G9 — every measured tree meets', 'app-modules/health/src is at 25.0%'),
        ];
    }

    /** @return list<Fixture> */
    public static function rulesAboutRules(): array
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

            // A tree held to a coverage floor that no gate reads, which is the
            // A port on the waiting list that something now takes. An edit
            // rather than a planted file: the register is about the repository
            // as it stands, so the violation is a real adapter reaching for the
            // kernel's spelling of a client rather than the narrowed one — which
            // is also the mistake that made the stand-in unreachable.
            Fixture::edit(
                'G8',
                'app-modules/sdk/src/Api/Questions.php',
                'public function __construct(private Clients $clients) {}',
                'public function __construct(private \\Modules\\Kernel\\Api\\Reaching $clients) {}',
                'a port that has grown a consumer',
                'Reaching',
            ),

            // whole of what `R4` is about and is not a file: the violation is a
            // line in `phpunit.xml` saying a directory is measured, with the
            // analyser's paths, the refactorer's paths and the autoloader all
            // silent about it. `lang/` is a real directory of real PHP and is
            // none of those things, so naming it as source is the smallest true
            // form of the mistake.
            // A model binding naming state the screen does not keep. Read by
            // `F10` off the markup and the class, because since
            // `nativephp/mobile` 4.5 a binding expands to
            // `data_get(get_defined_vars(), …)`: a name nothing supplies draws
            // an empty field and raises nothing, so no render — here or on a
            // phone — can see it any more.
            //
            // The binding, and not the hand-off in `render()`. A screen's state
            // is public, and the package fills a view's data from public
            // properties, so the field reaches the view whether or not
            // `render()` names it — deleting the hand-off plants nothing.
            Fixture::edit(
                'F10',
                'app-modules/operator/resources/views/pair-by-scanning.blade.php',
                'native:model="called"',
                'native:model="calledAndNotKept"',
                'every property a template binds',
                'calledAndNotKept',
            ),

            // A frame that cannot be drawn, which is the whole of `F15`: a
            // template echoing a variable nothing supplies. Unlike a binding
            // this still raises under every version of the package — Blade
            // compiles it to a bare `$variable` and a promoted warning is what
            // the walk catches. Planted on a line the screen always draws, so
            // the violation is reached whichever branch the stand-ins take.
            Fixture::edit(
                'F15',
                'app-modules/operator/resources/views/sign-into-a-stack.blade.php',
                '<native:text>{{ __($this->went()->remedy()) }}</native:text>',
                '<native:text>{{ $nothingSuppliesThis }}</native:text>',
                'every screen the router serves draws',
                'nothingSuppliesThis',
            ),

            // The component half of the same rule. Its own fixture rather
            // than one standing for both: the two walks start differently —
            // one at a method a screen declares, one at a property a component
            // was handed — and a fixture proving one proves nothing about the
            // other.
            Fixture::edit(
                'F14',
                'app-modules/operator/resources/views/components/what-stopped-the-reading.blade.php',
                '{{ __($went->remedy) }}',
                '{{ __($went->remedyish) }}',
                'every step a component takes',
                'remedyish',
            ),

            // A field renamed in the markup and nowhere else. An edit rather
            // than a planted file, because the violation is a template that
            // belongs to a screen — a new pair would need a screen written to
            // hold the mistake, and then the rule would be reading a fixture's
            // own class rather than the join this repository actually has.
            Fixture::edit(
                'F14',
                'app-modules/operator/resources/views/how-this-stack-is.blade.php',
                '{{ __($this->answer()->overall) }}',
                '{{ __($this->answer()->overallish) }}',
                'every step a template takes',
                'overallish',
            ),

            Fixture::edit(
                'R4',
                'phpunit.xml',
                '            <directory>bridge/src</directory>',
                "            <directory>bridge/src</directory>\n            <directory>lang</directory>",
                'R4 —',
                'lang',
            ),
        ];
    }
}
