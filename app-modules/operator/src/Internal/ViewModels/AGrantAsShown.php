<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * One grant of a credential to a third-party plugin, flattened for a template.
 *
 * There is no field for a value: a grant is named by the setting the
 * credential is held under.
 */
final readonly class AGrantAsShown
{
    /**
     * @param ?int       $which      its place among the grants the stack listed, which a revoke is tapped with, or nothing where it is drawn on its own
     * @param string     $plugin     the plugin it is granted to
     * @param string     $credential the setting the credential is held under
     * @param string     $whose      the service whose credential it is
     * @param string     $fills      the capability of the plugin's own it fills
     * @param AgoAsShown $approved   how long ago the operator approved it, or nothing where the stack does not say
     */
    public function __construct(
        public ?int $which,
        public string $plugin,
        public string $credential,
        public string $whose,
        public string $fills,
        public AgoAsShown $approved,
    ) {}
}
