<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\FetchingArtwork;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;

/**
 * A stack answering every poster and cover asked of it the one way a test says, counting how often it was asked and naming the kept asks whose covers were.
 */
final class AStackWithPosters implements FetchingArtwork
{
    private int $asked = 0;

    /** @var list<string> */
    private array $covers = [];

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

    public function coverOfAKeptAsk(Stack $stack, Session $session, RequestId $ask): HowArtworkCame
    {
        $this->asked++;
        $this->covers[] = $ask->reference();

        return $this->answer;
    }

    /**
     * The references of the kept asks whose covers were asked for, in the order asked.
     *
     * @return list<string>
     */
    public function coversAskedFor(): array
    {
        return $this->covers;
    }

    /** How many posters were asked for. */
    public function asked(): int
    {
        return $this->asked;
    }
}
