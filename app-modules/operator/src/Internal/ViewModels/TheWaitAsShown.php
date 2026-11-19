<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * How long to wait before trying to sign in again, as the screen says it, fixed when the refusal arrived.
 */
final readonly class TheWaitAsShown
{
    /**
     * @param string $said  the key for the line, empty where the stack named no wait
     * @param int    $count the whole minutes, or the seconds below one minute
     */
    public function __construct(
        public string $said,
        public int $count,
    ) {}
}
