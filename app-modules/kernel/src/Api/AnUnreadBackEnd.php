<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** A back-end whose record the core could not read, and the core's reason. */
final readonly class AnUnreadBackEnd
{
    private function __construct(public AlertBackEndId $id, public string $because) {}

    public static function because(AlertBackEndId $id, string $because): self
    {
        if (trim($because) === '') {
            throw WhereAlertsGoSaysNothing::about('why a back-end could not be read');
        }

        return new self($id, $because);
    }
}
