<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One line a walkthrough said: the step, what it was doing, and what was particular about it.
 *
 * Carried exactly as the stack said it. The narration is the operator's mental
 * model being built, so nothing here rewrites, trims or composes a line.
 */
final readonly class ALineItSaid
{
    private function __construct(
        private WalkthroughStep $step,
        private string $said,
        private ?string $detail,
    ) {}

    /** A line with the evidence that makes it worth reading; a blank sentence or detail is refused. */
    public static function withDetail(WalkthroughStep $step, string $said, string $detail): self
    {
        if (trim($detail) === '') {
            throw TheWalkthroughSaysNothing::about('detail');
        }

        return new self($step, self::sentence($said), $detail);
    }

    /** A line with nothing particular to say beyond what it was doing. */
    public static function withoutDetail(WalkthroughStep $step, string $said): self
    {
        return new self($step, self::sentence($said), null);
    }

    public function step(): WalkthroughStep
    {
        return $this->step;
    }

    /** What it was doing, in the stack's plain words. */
    public function said(): string
    {
        return $this->said;
    }

    /**
     * What was specifically true of it, or that the stack had nothing particular to say.
     *
     * @template T of object
     *
     * @param Closure(string): T $said
     * @param Closure(): T       $nothing
     *
     * @return T
     */
    public function detail(Closure $said, Closure $nothing): object
    {
        return $this->detail === null ? $nothing() : $said($this->detail);
    }

    private static function sentence(string $said): string
    {
        if (trim($said) === '') {
            throw TheWalkthroughSaysNothing::about('said');
        }

        return $said;
    }
}
