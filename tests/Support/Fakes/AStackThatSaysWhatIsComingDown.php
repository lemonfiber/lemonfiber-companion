<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\AskingWhatAStopWouldInterrupt;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheDownloadsComingDown;
use Modules\Kernel\Api\WhatAStopReaches;
use Modules\Kernel\Api\WhatWasFoundComingDown;
use Override;

/**
 * A stack asked what is still coming down inside a stop, answering one thing whatever it is asked about, and keeping what it was asked about.
 */
final class AStackThatSaysWhatIsComingDown implements AskingWhatAStopWouldInterrupt
{
    /** @var list<WhatAStopReaches> */
    private array $asked = [];

    private function __construct(private readonly WhatWasFoundComingDown $answer) {}

    /** Nothing is coming down. */
    public static function nothing(): self
    {
        return new self(WhatWasFoundComingDown::said(TheDownloadsComingDown::these()));
    }

    /** A film over Usenet at forty per cent, and a show by torrent at ninety-four. */
    public static function twoDownloads(): self
    {
        return self::these(
            ADownloadItWouldInterrupt::inFlight('A film', HowADownloadComes::Usenet, 40),
            ADownloadItWouldInterrupt::inFlight('A show', HowADownloadComes::Torrent, 94),
        );
    }

    /** These are coming down. */
    public static function these(ADownloadItWouldInterrupt ...$coming): self
    {
        return new self(WhatWasFoundComingDown::said(TheDownloadsComingDown::these(...$coming)));
    }

    /** The stack could not be asked, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(WhatWasFoundComingDown::met($why));
    }

    /**
     * What it was asked about, in order.
     *
     * @return list<WhatAStopReaches>
     */
    public function asked(): array
    {
        return $this->asked;
    }

    #[Override]
    public function inside(Stack $stack, Session $session, WhatAStopReaches $reach): WhatWasFoundComingDown
    {
        $this->asked[] = $reach;

        return $this->answer;
    }
}
