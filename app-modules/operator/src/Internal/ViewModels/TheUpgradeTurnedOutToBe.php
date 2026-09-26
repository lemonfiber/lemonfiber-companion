<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack about upgrading the library produced, flattened for a template.
 *
 * Drawn inside the quality screen rather than in place of it, so what stood
 * in the way is drawn under the upgrade's heading rather than over the whole
 * screen.
 */
final readonly class TheUpgradeTurnedOutToBe
{
    /**
     * @param string                 $headingSaid the catalogue key for what it came to, or empty where nothing came back
     * @param list<AnUpgradeAsShown> $kinds       every kind of media it covers, in the stack's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $headingSaid,
        public array $kinds,
    ) {}
}
