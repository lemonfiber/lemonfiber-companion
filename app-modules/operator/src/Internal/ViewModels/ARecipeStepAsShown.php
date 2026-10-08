<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One call a recipe makes, with the adapter of lemonfiber's it reaches through, where there is one. */
final readonly class ARecipeStepAsShown
{
    /**
     * @param string $method  the method it calls with
     * @param string $to      where it calls, by the manifest's name
     * @param string $path    the path it calls
     * @param string $adapter which of lemonfiber's adapters reaches it, or empty
     */
    public function __construct(
        public string $method,
        public string $to,
        public string $path,
        public string $adapter,
    ) {}
}
