<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One moment in a traced item's history, flattened for a template. */
final readonly class AMomentAsShown
{
    /**
     * @param string $said the catalogue key for what happened
     * @param string $at   when the service said it did
     */
    public function __construct(public string $said, public string $at) {}
}
