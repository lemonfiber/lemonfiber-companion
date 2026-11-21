<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Whether the stack starts with the machine, as the stack says, and the commands that change it at the machine. */
final readonly class AutostartAsShown
{
    /** @param string $standsSaid the catalogue key for where it stands */
    public function __construct(public string $standsSaid, public string $install, public string $remove) {}
}
