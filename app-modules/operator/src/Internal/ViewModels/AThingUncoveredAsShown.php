<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One thing a monitor covers, or that no monitor covers, as the watcher screen draws it.
 */
final readonly class AThingUncoveredAsShown
{
    /**
     * @param string $said the key naming its kind
     * @param string $name its name, as the stack gave it
     */
    public function __construct(public string $said, public string $name) {}
}
