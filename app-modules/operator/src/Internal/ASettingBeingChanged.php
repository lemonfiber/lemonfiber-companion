<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** The setting open for changing, by its name, and whether it is being put back to its default rather than given a value. */
final readonly class ASettingBeingChanged
{
    public function __construct(public string $named, public bool $toDefault) {}
}
