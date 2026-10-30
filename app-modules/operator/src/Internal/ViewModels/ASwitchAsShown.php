<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One approval's switch: its place among the approvals the account offers, and whether the operator has turned it on. */
final readonly class ASwitchAsShown
{
    public function __construct(public int $place, public bool $on) {}
}
