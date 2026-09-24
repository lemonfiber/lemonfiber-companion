<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * How the running copy got onto its machine, and the tool that owns it where one does.
 *
 * Together because they answer one question, *who replaces this*: a copy a
 * package manager installed is that tool's to replace.
 */
final readonly class HowThisCopyGotThere
{
    private function __construct(private HowLemonfiberWasInstalled $installed, private string $owner) {}

    /**
     * How it was installed, and its owner, which is empty where none does own it.
     *
     * A blank owner is refused: blank is a name with nothing in it.
     */
    public static function by(HowLemonfiberWasInstalled $installed, string $owner): self
    {
        if ($owner !== '' && trim($owner) === '') {
            throw ItselfSaysNothing::about('owner');
        }

        return new self($installed, $owner);
    }

    /** How this copy got onto the machine. */
    public function installed(): HowLemonfiberWasInstalled
    {
        return $this->installed;
    }

    /** The tool that owns this copy, or empty where none does. */
    public function owner(): string
    {
        return $this->owner;
    }
}
