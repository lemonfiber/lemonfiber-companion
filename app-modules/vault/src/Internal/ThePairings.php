<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StacksBeingRemoved;
use Modules\Kernel\Api\WhyTheStacksAreHeldBack;

/** Every pairing this phone holds, and every stack whose removal from it has begun. */
final readonly class ThePairings
{
    public function __construct(public Configured $stacks, public StacksBeingRemoved $removing) {}

    public static function none(): self
    {
        return new self(Configured::none(), StacksBeingRemoved::none());
    }

    /** A record that is there and could not be read, which nothing may be written over. */
    public static function heldBack(WhyTheStacksAreHeldBack $why): self
    {
        return new self(Configured::heldBack($why), StacksBeingRemoved::none());
    }

    /** Whether the record could not be read, so writing one down would replace stacks still in it. */
    public function isHeldBack(): bool
    {
        return $this->stacks->isHeldBack();
    }

    /** Whether there is nothing to write down: no pairing, and no removal under way. */
    public function isEmpty(): bool
    {
        return $this->stacks->isEmpty() && $this->removing->isEmpty();
    }

    /** The pairings a list draws: every one, less those being removed. */
    public function listed(): Configured
    {
        $listed = $this->stacks;

        foreach ($this->removing as $being) {
            $listed = $listed->without($being);
        }

        return $listed;
    }

    /** These, with this stack paired; pairing it again is taking it back, so a removal of it is over. */
    public function pairing(Stack $stack): self
    {
        return new self($this->stacks->with($stack), $this->removing->without($stack->id()));
    }

    /** These, without the pairing of this stack; a removal of it stays under way until it is finished. */
    public function unpairing(StackId $stack): self
    {
        return new self($this->stacks->without($stack), $this->removing);
    }

    /** These, with the stacks in the order the operator put them. */
    public function inTheOrderOf(StackId ...$order): self
    {
        return new self($this->stacks->inTheOrderOf(...$order), $this->removing);
    }

    /** These, with the removal of this stack begun. */
    public function removing(StackId $stack): self
    {
        return new self($this->stacks, $this->removing->with($stack));
    }

    /** These, with the removal of this stack finished. */
    public function removed(StackId $stack): self
    {
        return new self($this->stacks, $this->removing->without($stack));
    }
}
