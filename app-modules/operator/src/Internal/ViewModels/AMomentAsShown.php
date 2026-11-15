<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** One moment in a traced item's history, flattened for a template. */
final readonly class AMomentAsShown
{
    /**
     * @param string $said the catalogue key for what happened
     * @param AgoAsShown $at how long ago the service said it did, saying nothing where it gave no time
     */
    public function __construct(public string $said, public AgoAsShown $at) {}
}
