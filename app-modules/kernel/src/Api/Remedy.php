<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing to do about a problem, phrased as something to do.
 *
 * The phrasing is the requirement rather than a style note. "The drive is
 * full" is a fact and leaves the operator where they started; "free 20 GB on
 * the media drive" is a remedy. The server writes them that way and this side
 * carries them without rewording, because a translation layer that rephrases
 * an instruction is a place for it to become subtly wrong.
 */
final readonly class Remedy
{
    private function __construct(private string $action) {}

    /**
     * The one place a string becomes a remedy.
     *
     * Empty is refused. A remedy list with a blank row in it renders as a
     * button with no label, which is worse than one fewer remedy.
     */
    public static function of(string $action): self
    {
        $trimmed = trim($action);

        if ($trimmed === '') {
            throw RemedySaysNothing::inAProblem();
        }

        return new self($trimmed);
    }

    public function action(): string
    {
        return $this->action;
    }
}
