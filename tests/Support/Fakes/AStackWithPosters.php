<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\FetchingArtwork;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;

/**
 * A stack answering every poster asked of it the one way a test says, and counting how often it was asked.
 */
final class AStackWithPosters implements FetchingArtwork
{
    private int $asked = 0;

    private function __construct(private readonly HowArtworkCame $answer) {}

    public static function answering(HowArtworkCame $answer): self
    {
        return new self($answer);
    }

    public function posterOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): HowArtworkCame
    {
        $this->asked++;

        return $this->answer;
    }

    public function backdropOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): HowArtworkCame
    {
        $this->asked++;

        return $this->answer;
    }

    public function posterOfAFoundTitle(Stack $stack, Session $session, AFoundTitle $title): HowArtworkCame
    {
        $this->asked++;

        return $this->answer;
    }

    /** How many posters were asked for. */
    public function asked(): int
    {
        return $this->asked;
    }
}
