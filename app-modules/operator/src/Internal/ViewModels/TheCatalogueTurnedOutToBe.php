<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what its services are for produced, flattened for a template.
 *
 * The sibling of {@see TheOriginsTurnedOutToBe}, written the same way.
 */
final readonly class TheCatalogueTurnedOutToBe
{
    /**
     * @param list<AServiceAsCatalogued>   $services every service the stack declares, in its order
     * @param list<AServiceDroppedAsShown> $dropped  every service it dropped, in the order it records them
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $services,
        public array $dropped,
    ) {}
}
