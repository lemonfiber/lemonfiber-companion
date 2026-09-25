<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service the stack left out of the forms asked for, and why.
 *
 * Filtered, not failed: a form's services are intersected with what the
 * operator configured, and this one needs something the stack does not have.
 * Said, it is the feature working; drawn as absent, it is a fault somebody
 * goes looking for.
 */
final readonly class AServiceLeftOut
{
    private function __construct(
        private ServiceId $id,
        private string $name,
        private WhatItWouldNeed $needs,
        private Forms $askedBy,
    ) {}

    /** A service named as the stack names it; a blank name is refused. */
    public static function needing(ServiceId $id, string $name, WhatItWouldNeed $needs, Forms $askedBy): self
    {
        if (trim($name) === '') {
            throw TheRehearsalSaysNothing::about('name');
        }

        return new self($id, $name, $needs, $askedBy);
    }

    public function id(): ServiceId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** What it would have needed to be started. */
    public function needs(): WhatItWouldNeed
    {
        return $this->needs;
    }

    /** The forms that asked for it. */
    public function askedBy(): Forms
    {
        return $this->askedBy;
    }
}
