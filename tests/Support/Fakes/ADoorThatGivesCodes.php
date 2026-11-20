<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;
use function count;

use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\AdmittingByACode;
use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\CodeAsked;
use Modules\Kernel\Api\HowTheCodeStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Stack;

/**
 * A door that answers asking for a code, and finishing one, as a test says.
 *
 * Finishing answers what it was given in turn and then the last of it again,
 * and that the code still waits where it was given nothing.
 */
final class ADoorThatGivesCodes implements AdmittingByACode
{
    private int $asks = 0;

    private int $finishes = 0;

    private ?string $finishedWith = null;

    /** @param list<HowTheCodeStands> $answers */
    private function __construct(private readonly CodeAsked $asked, private array $answers) {}

    /** A door that issues this code, and then answers finishing it as given. */
    public static function issuing(ACodeToApprove $code, HowTheCodeStands ...$then): self
    {
        return new self(CodeAsked::issued($code), $then === [] ? [HowTheCodeStands::waiting($code->lapses)] : array_values($then));
    }

    /** A door that answers asking for a code this way, and has nothing to finish. */
    public static function answering(CodeAsked $asked): self
    {
        return new self($asked, [HowTheCodeStands::waiting(Instant::atEpochSeconds(0))]);
    }

    public function askForACode(Stack $stack): CodeAsked
    {
        $this->asks++;

        return $this->asked;
    }

    public function finishByCode(Stack $stack, AWaitingCode $waiting): HowTheCodeStands
    {
        $this->finishes++;
        $this->finishedWith = $waiting->forTheExchange();

        return count($this->answers) > 1 ? array_shift($this->answers) : $this->answers[0];
    }

    /** How many times a code was asked for. */
    public function asks(): int
    {
        return $this->asks;
    }

    /** How many times the house was asked whether the code was approved. */
    public function finishes(): int
    {
        return $this->finishes;
    }

    /** The handle the last finish carried, or nothing before one. */
    public function finishedWith(): ?string
    {
        return $this->finishedWith;
    }
}
