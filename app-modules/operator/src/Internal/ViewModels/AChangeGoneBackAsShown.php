<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One change putting a run back reversed, or would, as a template draws it. */
final readonly class AChangeGoneBackAsShown
{
    /**
     * @param string $target  the service or file it was put back against
     * @param string $doesSaid the key for what going back did to it
     */
    public function __construct(
        public string $target,
        public string $doesSaid,
    ) {}
}
