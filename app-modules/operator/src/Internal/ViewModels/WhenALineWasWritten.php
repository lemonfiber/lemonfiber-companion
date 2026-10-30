<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** When a service wrote a line: on the phone's clock where the moment can be read, and as the service wrote it, for a screen reader. */
final readonly class WhenALineWasWritten
{
    public function __construct(public string $at, public string $inFull) {}
}
