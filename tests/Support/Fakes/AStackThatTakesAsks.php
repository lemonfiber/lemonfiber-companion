<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\AskingForTitles;
use Modules\Kernel\Api\HowAnAskWent;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;

use function sprintf;

/**
 * A stack answering a rehearsal and an ask as a test says, and remembering what it was sent and under which key.
 */
final class AStackThatTakesAsks implements AskingForTitles
{
    /** @var list<string> */
    private array $rehearsed = [];

    /** @var list<string> */
    private array $asked = [];

    private function __construct(private readonly HowAnAskWent $rehearsal, private HowAnAskWent $ask) {}

    /** A stack answering the rehearsal and the ask this way. */
    public static function answering(HowAnAskWent $rehearsal, HowAnAskWent $ask): self
    {
        return new self($rehearsal, $ask);
    }

    public function rehearse(Stack $stack, Session $session, AFoundTitle $title): HowAnAskWent
    {
        $this->rehearsed[] = sprintf('%s %s', $title->kind()->value, $title->id());

        return $this->rehearsal;
    }

    public function ask(Stack $stack, Session $session, AFoundTitle $title, IdempotencyKey $key): HowAnAskWent
    {
        $this->asked[] = sprintf('%s %s under %s', $title->kind()->value, $title->id(), $key->sent());

        return $this->ask;
    }

    /** From now on, answer an ask this way. */
    public function answerTheAskWith(HowAnAskWent $ask): void
    {
        $this->ask = $ask;
    }

    /** @return list<string> */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<string> */
    public function asked(): array
    {
        return $this->asked;
    }
}
