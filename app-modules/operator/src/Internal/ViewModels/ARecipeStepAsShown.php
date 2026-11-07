<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One call a recipe makes, with the API its destination is reached in, where its plugin declares one. */
final readonly class ARecipeStepAsShown
{
    /**
     * @param string $method the method it calls with
     * @param string $to     where it calls, by the manifest's name
     * @param string $path   the path it calls
     * @param string $api    the API its destination is reached in, or empty
     */
    public function __construct(
        public string $method,
        public string $to,
        public string $path,
        public string $api,
    ) {}
}
