<?php

declare(strict_types=1);

namespace Tests\Support;

use Tests\Support\Violations\Analysability;
use Tests\Support\Violations\ErrorsAndBoundaries;
use Tests\Support\Violations\FloorsAndRules;
use Tests\Support\Violations\Framework;
use Tests\Support\Violations\SizeAndPlacement;
use Tests\Support\Violations\SurfaceAndText;
use Tests\Support\Violations\Templates;
use Tests\Support\Violations\TestsAndNaming;
use Tests\Support\Violations\TextAndSecurity;
use Tests\Support\Violations\Unplanted;
use Tests\Support\Violations\WhatASurfaceIsNeverShown;

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
 * The fixtures live in `Tests\Support\Violations`, one class per family of
 * rules; this is the index that puts them in one list, in one order. Each is
 * planted in a throwaway copy of the checkout and never in the checkout itself,
 * so a path here can name any file the repository has.
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
            ...Framework::frameworkCoupling(),
            ...Framework::primitives(),
            ...ErrorsAndBoundaries::errorsAndShape(),
            ...ErrorsAndBoundaries::boundaries(),
            ...SurfaceAndText::surfaceAndText(),
            ...TestsAndNaming::testsAndNaming(),
            ...Analysability::analysability(),
            ...TextAndSecurity::textAndSecurity(),
            ...SizeAndPlacement::sizeAndSuiteIntegrity(),
            ...SizeAndPlacement::placement(),
            ...Templates::templates(),
            ...Templates::screensNobodyCanReach(),
            ...FloorsAndRules::floors(),
            ...FloorsAndRules::rulesAboutRules(),
            ...WhatASurfaceIsNeverShown::whatASurfaceIsNeverShown(),
            ...Unplanted::drivenDirectly(),
            ...Unplanted::notDrivable(),
        ];
    }
}
