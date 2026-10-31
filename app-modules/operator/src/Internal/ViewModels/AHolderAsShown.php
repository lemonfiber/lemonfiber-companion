<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One service that holds the credential, and how far a replacement reached it, flattened for a template.
 */
final readonly class AHolderAsShown
{
    /**
     * @param string $holder    the service, by name
     * @param string $reachSaid the catalogue key for how far the replacement reached it
     * @param string $detail    what still has to happen, or why it could not be reached; empty where it holds the replacement
     */
    public function __construct(
        public string $holder,
        public string $reachSaid,
        public string $detail,
    ) {}
}
