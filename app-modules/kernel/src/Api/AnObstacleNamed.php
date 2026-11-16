<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Something in the way of one part of an answer, as the core named it: its code, its sentence and what to do about it.
 */
final readonly class AnObstacleNamed
{
    private function __construct(private Code $code, private Sentence $said, private Remedy $remedy) {}

    public static function of(Code $code, Sentence $said, Remedy $remedy): self
    {
        return new self($code, $said, $remedy);
    }

    /** The code that names what is in the way. */
    public function code(): Code
    {
        return $this->code;
    }

    /** What is in the way, in the core's one sentence. */
    public function said(): Sentence
    {
        return $this->said;
    }

    /** What to do about it. */
    public function remedy(): Remedy
    {
        return $this->remedy;
    }
}
