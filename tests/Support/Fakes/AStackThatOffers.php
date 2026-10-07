<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function in_array;

use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhetherItIsOffered;

/**
 * What a stack says it offers, as a test arranged it.
 *
 * Each stack answers for itself, as the stack it stands in for does: an
 * answer arranged for one is never given for another, and a stack nothing was
 * arranged for could not be asked. Asking again is recorded, so a screen's
 * *ask again* can be seen to reach the stack.
 */
final class AStackThatOffers implements KnowingWhatAStackOffers
{
    /** @var list<string> each stack asked again, by its stored identifier */
    private array $askedAgain = [];

    /** How many screens opened, each of which lets go of nothing here: what this says never ages. */
    private int $screensOpened = 0;

    /**
     * @param array<string, WhetherItIsOffered>                $otherwise each stack's answer for an action nothing names
     * @param array<string, array<string, WhetherItIsOffered>> $saying    each stack's answer for each action it names
     */
    private function __construct(
        private readonly array $otherwise,
        private readonly array $saying,
        private readonly WhetherItIsOffered $anyStack,
    ) {}

    /** Every stack offers every action. */
    public static function everything(): self
    {
        return new self([], [], WhetherItIsOffered::Offered);
    }

    /**
     * One stack, answering these for these actions and that for the rest; every other stack cannot be asked.
     *
     * @param array<string, WhetherItIsOffered> $saying by the name each action is asked by
     */
    public static function onTheStack(StackId $stack, WhetherItIsOffered $otherwise, array $saying = []): self
    {
        return new self([$stack->stored() => $otherwise], [$stack->stored() => $saying], WhetherItIsOffered::NotKnown);
    }

    /**
     * And another stack, answering for itself.
     *
     * @param array<string, WhetherItIsOffered> $saying by the name each action is asked by
     */
    public function andOnTheStack(StackId $stack, WhetherItIsOffered $otherwise, array $saying = []): self
    {
        return new self(
            [...$this->otherwise, $stack->stored() => $otherwise],
            [...$this->saying, $stack->stored() => $saying],
            $this->anyStack,
        );
    }

    public function whetherItOffers(Stack $stack, Session $session, AnAction $action): WhetherItIsOffered
    {
        $which = $stack->id()->stored();

        if (! array_key_exists($which, $this->otherwise)) {
            return $this->anyStack;
        }

        return $this->saying[$which][$action->asked()] ?? $this->otherwise[$which];
    }

    public function askAgain(StackId $stack): Forgotten
    {
        $this->askedAgain[] = $stack->stored();

        return array_key_exists($stack->stored(), $this->otherwise) ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function aScreenOpens(): Forgotten
    {
        ++$this->screensOpened;

        return Forgotten::nothing();
    }

    /** How many screens have opened. */
    public function screensOpened(): int
    {
        return $this->screensOpened;
    }

    /** Whether this stack was asked again. */
    public function wasAskedAgain(StackId $stack): bool
    {
        return in_array($stack->stored(), $this->askedAgain, strict: true);
    }
}
