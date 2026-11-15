<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * One healing run, as the queue screen draws it.
 */
final readonly class AHealingRunAsShown
{
    /**
     * @param AgoAsShown                 $ran       how long ago it ran
     * @param bool                       $rehearsed whether it was a rehearsal, which cleared nothing
     * @param list<AnItemActedOnAsShown> $items     every item it acted on, or would have
     */
    public function __construct(public AgoAsShown $ran, public bool $rehearsed, public array $items) {}
}
