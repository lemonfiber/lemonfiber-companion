<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One stage a traced item reached, flattened for a template. */
final readonly class AStageAsShown
{
    /**
     * @param string $word    the stage as the stack names it, drawn as it came
     * @param string $said    the catalogue key for the sentence beside it
     * @param string $service the service that recorded it
     * @param string $at      when the service said it happened, or empty where it was not timed
     */
    public function __construct(
        public string $word,
        public string $said,
        public string $service,
        public string $at,
    ) {}
}
