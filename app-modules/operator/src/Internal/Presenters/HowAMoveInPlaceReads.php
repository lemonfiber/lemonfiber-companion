<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMoveInPlace;
use Modules\Kernel\Api\TheMovesInPlace;
use Modules\Kernel\Api\WhatBecameOfTheMovesInPlace;
use Modules\Operator\Internal\ViewModels\AMoveInPlaceAsShown;

/** Moves in place, as the rows a screen draws: on offer, or as an update took them. */
final readonly class HowAMoveInPlaceReads
{
    /** A move an update will take, on its confirmation. */
    public const string WOULD_TAKE = 'updates.moves.would_take';

    /** A move on offer that a start named and did not take. */
    public const string ON_OFFER = 'updates.moves.on_offer';

    /** A move an update took. */
    public const string TOOK = 'updates.moves.took';

    /** A move an update tried and did not take. */
    public const string DID_NOT_TAKE = 'updates.moves.did_not_take';

    /**
     * Each move on offer, said with `$said`.
     *
     * @return list<AMoveInPlaceAsShown>
     */
    public function offered(TheMovesInPlace $moves, string $said): array
    {
        $shown = [];

        foreach ($moves as $move) {
            $shown[] = $this->row($move, $said, owesAReason: false, because: '');
        }

        return $shown;
    }

    /**
     * Each move an update tried, taken or not taken and why.
     *
     * @return list<AMoveInPlaceAsShown>
     */
    public function tried(WhatBecameOfTheMovesInPlace $moves): array
    {
        $shown = [];

        foreach ($moves as $went) {
            $shown[] = $went->wasTaken()
                ? $this->row($went->move(), self::TOOK, owesAReason: false, because: '')
                : $this->row($went->move(), self::DID_NOT_TAKE, owesAReason: true, because: $went->why());
        }

        return $shown;
    }

    private function row(AMoveInPlace $move, string $said, bool $owesAReason, string $because): AMoveInPlaceAsShown
    {
        return new AMoveInPlaceAsShown(
            said: $said,
            service: $move->service()->named(),
            plugin: $move->plugin()->named(),
            owesAReason: $owesAReason,
            because: $because,
        );
    }
}
