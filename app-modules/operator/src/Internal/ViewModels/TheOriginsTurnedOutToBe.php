<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack where its services come from produced, flattened for a template.
 *
 * The sibling of {@see TheRecordTurnedOutToBe}, written the same way:
 * {@see \Modules\Operator\Internal\Presenters\HowTheOriginsRead} folds the
 * answer once and the template reads fields.
 *
 * **An empty list means the stack answered and declares nothing.** A stack
 * that could not be asked carries an obstacle in `went` instead, and the
 * template never reaches the list for it.
 */
final readonly class TheOriginsTurnedOutToBe
{
    /** @param list<WhereOneServiceComesFrom> $services every service, in the order the stack declares them */
    public function __construct(
        public HowTheReadingWent $went,
        public array $services,
    ) {}
}
