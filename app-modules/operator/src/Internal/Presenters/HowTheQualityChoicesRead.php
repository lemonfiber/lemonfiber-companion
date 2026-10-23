<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Operator\Internal\ViewModels\AStacksWordOnAChip;

/**
 * The words a quality choice is made from, as the chips the template draws.
 */
final readonly class HowTheQualityChoicesRead
{
    /**
     * Each word offered, in the stack's order, the one chosen marked.
     *
     * @param list<string> $offered the stack's words
     *
     * @return list<AStacksWordOnAChip>
     */
    public function chips(array $offered, string $chosen): array
    {
        $chips = [];

        foreach ($offered as $word) {
            $chips[] = new AStacksWordOnAChip(word: $word, chosen: $word === $chosen);
        }

        return $chips;
    }
}
