<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One file of a support bundle, flattened for a template: its name, and everything it holds. */
final readonly class APieceAsShown
{
    /**
     * @param string $name what the file is called inside the bundle
     * @param string $body what it holds, exactly as the stack redacted it
     */
    public function __construct(
        public string $name,
        public string $body,
    ) {}
}
