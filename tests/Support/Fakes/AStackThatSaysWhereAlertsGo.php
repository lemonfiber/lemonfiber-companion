<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\KnowingWhereAlertsGo;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Kernel\Api\WhereAlertsGoCameBack;
use Override;

/** A machine that says where its alerts go, or meets the operator with an obstacle, counting how often it is asked. */
final class AStackThatSaysWhereAlertsGo implements KnowingWhereAlertsGo
{
    public int $askings = 0;

    private function __construct(private readonly WhereAlertsGoCameBack $answer) {}

    public static function saying(WhereAlertsGo $read): self
    {
        return new self(WhereAlertsGoCameBack::read($read));
    }

    public static function met(Obstacle $why): self
    {
        return new self(WhereAlertsGoCameBack::met($why));
    }

    #[Override]
    public function whereAlertsGoOn(Stack $stack, Session $session): WhereAlertsGoCameBack
    {
        $this->askings++;

        return $this->answer;
    }
}
