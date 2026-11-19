<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function intdiv;

use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Operator\Internal\ViewModels\TheWaitAsShown;

/**
 * Turns the wait a refused sign-in named into "Try again in N min", rounded up to whole minutes and in seconds below one.
 */
final readonly class HowTheWaitReads
{
    /** The wait the stack named, or no line where it named none. */
    public function of(WhenToTryAgain $when): TheWaitAsShown
    {
        $seconds = $when->wait()->inSeconds();

        return match (true) {
            ! $when->isSaid() || $seconds === 0 => $this->none(),
            $seconds < SecondsIn::AMinute->value => new TheWaitAsShown('connection.try_again_in.seconds', $seconds),
            default => new TheWaitAsShown('connection.try_again_in.minutes', intdiv($seconds + SecondsIn::AMinute->value - 1, SecondsIn::AMinute->value)),
        };
    }

    /** No line. */
    public function none(): TheWaitAsShown
    {
        return new TheWaitAsShown('', 0);
    }
}
