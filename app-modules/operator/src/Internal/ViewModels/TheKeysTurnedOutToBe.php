<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack for its integration keys produced, flattened for a template.
 *
 * An empty list on a reading that came back is a stack listing none; a reading
 * that did not come back carries its obstacle in `went`, and the template
 * draws that instead of the list.
 */
final readonly class TheKeysTurnedOutToBe
{
    /**
     * @param list<AKeyAsShown> $keys     every key, in the order they were minted
     * @param string            $purposes the stack's sentence on what a purpose is worth, or empty where nothing came back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $keys,
        public string $purposes,
    ) {}
}
