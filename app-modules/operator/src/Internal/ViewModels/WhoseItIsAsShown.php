<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Whose a setting or a secret is, flattened for a template. */
final readonly class WhoseItIsAsShown
{
    /**
     * @param string $said      the catalogue key saying whose it is, blank where it is the plugin's own
     * @param string $namespace the namespace it belongs to, blank where it is the core's
     */
    public function __construct(public string $said, public string $namespace) {}
}
