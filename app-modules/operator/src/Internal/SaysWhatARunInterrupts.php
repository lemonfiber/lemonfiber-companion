<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\TheHealthSummary;
use Modules\Operator\Internal\ViewModels\TheDownloadsMovingAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * The downloads a run of checks that disturb would interrupt, as the stack last said them on the screen's own stream.
 *
 * @phpstan-require-extends NativeComponent
 */
trait SaysWhatARunInterrupts
{
    /**
     * The downloads the stack said are moving in what this screen last heard, which a run interrupts.
     *
     * Only from a summary heard now: a kept one names none, because a
     * download moves on long before it is opened again.
     *
     * @return list<string>
     */
    public function downloadsMoving(): array
    {
        return $this->heardSoFar()->summary(
            none: static fn(): TheDownloadsMovingAsShown => TheDownloadsMovingAsShown::none(),
            current: static fn(TheHealthSummary $summary): TheDownloadsMovingAsShown => TheDownloadsMovingAsShown::in($summary),
            asOf: static fn(): TheDownloadsMovingAsShown => TheDownloadsMovingAsShown::none(),
        )->names;
    }
}
