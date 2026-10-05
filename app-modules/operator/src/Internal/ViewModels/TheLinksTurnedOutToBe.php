<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what it wires to what produced, flattened for a template.
 *
 * The sibling of {@see TheCatalogueTurnedOutToBe}, written the same way. A
 * stack that asks nothing of its services came back with no link and no
 * refusal; one that could not read its wiring came back with a refusal and is
 * never drawn as settled.
 */
final readonly class TheLinksTurnedOutToBe
{
    /**
     * @param list<ALinkAsShown> $links   every link, in the order the stack declares them
     * @param ?ARefusalAsShown   $refused why the stack could not say, in its words, and nothing unless it refused
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $links,
        public ?ARefusalAsShown $refused,
    ) {}
}
