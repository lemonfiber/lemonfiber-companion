<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A curator or download client the queue could not reach, as the queue screen draws it.
 */
final readonly class AServiceUnreachedAsShown
{
    /**
     * @param string $service the service, by its id
     * @param string $why     why, in the stack's words
     */
    public function __construct(public string $service, public string $why) {}
}
