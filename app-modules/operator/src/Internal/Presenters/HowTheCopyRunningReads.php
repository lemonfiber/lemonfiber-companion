<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Health\Api\WhatAStreamSaidSoFar;
use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;

use function sprintf;

/**
 * How the backup or restore running on a stack reads, from the stack's reading alone.
 *
 * Only a reading the stream still vouches for is drawn. With none, or with one
 * the stream has stopped carrying, the screen says it cannot tell and draws
 * neither working nor stalled: how a copy is going is the stack's to say.
 */
final readonly class HowTheCopyRunningReads
{
    public const string CANNOT_TELL = 'stacks.copy.running.cannot_tell';

    public const string NONE = 'stacks.copy.running.none';

    public const string WORKING = 'stacks.copy.running.working';

    public const string PACED = 'stacks.copy.running.paced';

    private const string STALLED = 'stacks.copy.running.stalled.%s';

    /**
     * What the screen has heard, as it stands at the frame's moment.
     *
     * @param WhatAStreamSaidSoFar<WhatIsBeingCopied> $heard
     */
    public function of(WhatAStreamSaidSoFar $heard, Instant $now): ACopyRunningAsShown
    {
        return $heard->heard(
            none: static fn(): ACopyRunningAsShown => new ACopyRunningAsShown(self::CANNOT_TELL, Tone::Unknown->value),
            current: fn(WhatIsBeingCopied $copying): ACopyRunningAsShown => $copying->either(
                nothing: static fn(): ACopyRunningAsShown => new ACopyRunningAsShown(self::NONE, Tone::Quiet->value),
                running: fn(ACopyRunning $running): ACopyRunningAsShown => $this->running($running, $now),
            ),
            asOf: static fn(): ACopyRunningAsShown => new ACopyRunningAsShown(self::CANNOT_TELL, Tone::Unknown->value),
        );
    }

    private function running(ACopyRunning $running, Instant $now): ACopyRunningAsShown
    {
        $shown = static fn(string $moves, Tone $tone, ?ASizeAsShown $limit = null, int $count = 0): ACopyRunningAsShown => new ACopyRunningAsShown(
            said: $running->what->saidOnTheScreen(),
            tone: $tone->value,
            written: self::size($running->written),
            whole: self::size($running->whole),
            part: $running->part,
            moves: $moves,
            limit: $limit,
            count: $count,
        );

        return $running->moves->either(
            working: static fn(): ACopyRunningAsShown => $shown(self::WORKING, Tone::Working),
            paced: static fn(int $limit): ACopyRunningAsShown => $shown(self::PACED, Tone::Working, self::size($limit)),
            stalled: static function (Instant $wrote) use ($shown, $now): ACopyRunningAsShown {
                $since = HowLongAgo::since($wrote, $now);

                return $shown(sprintf(self::STALLED, $since->value), Tone::Trouble, count: $since->howManySince($wrote, $now));
            },
        );
    }

    private static function size(int $bytes): ASizeAsShown
    {
        $big = HowBig::of($bytes);

        return new ASizeAsShown(figure: $big->figure, unit: $big->said);
    }
}
