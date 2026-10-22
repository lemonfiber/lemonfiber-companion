<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One move in place, flattened to a row. */
final readonly class AMoveInPlaceAsShown
{
    /**
     * @param string $said        the catalogue key the row is said with, naming `:plugin` and `:service`
     * @param string $service     the stack's own service
     * @param string $plugin      the plugin that takes, or would take, its place
     * @param bool   $owesAReason whether the move was not taken, so the row says why
     * @param string $because     why it was not taken, in the stack's words, or empty where it gave none
     */
    public function __construct(
        public string $said,
        public string $service,
        public string $plugin,
        public bool $owesAReason,
        public string $because,
    ) {}
}
