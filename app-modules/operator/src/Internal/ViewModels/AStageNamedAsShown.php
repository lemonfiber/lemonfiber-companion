<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A stage named on its own, with no service or time beside it: how far an item got. */
final readonly class AStageNamedAsShown
{
    /**
     * @param string $word the stage as the stack names it, drawn as it came
     * @param string $said the catalogue key for the sentence beside it
     */
    public function __construct(
        public string $word,
        public string $said,
    ) {}
}
