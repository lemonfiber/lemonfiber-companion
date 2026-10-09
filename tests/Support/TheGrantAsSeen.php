<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\TheGrantHeld;

use function sprintf;

/** A grant held, folded to one line an assertion can compare: its token and when it lapses, or that none is held. */
final readonly class TheGrantAsSeen
{
    /** What a held grant is seen as where none is held. */
    public const string NONE = 'no grant';

    public static function of(TheGrantHeld $held): string
    {
        return $held->either(
            held: static fn(AGrant $grant): Code => Code::of(sprintf('%s until %d', $grant->forTheDoor(), $grant->lapsesAt()->epochSeconds())),
            none: static fn(): Code => Code::of(self::NONE),
        )->shown();
    }
}
