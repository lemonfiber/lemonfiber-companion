<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Who is watching on the media server now, which a restart would interrupt, flattened for a template.
 */
final readonly class WhoIsWatchingAsShown
{
    /**
     * @param bool         $read     whether what is playing could be read at all
     * @param list<array{member: string, device: string}> $sessions each session, by the member and the device they watch on
     */
    public function __construct(
        public bool $read,
        public array $sessions,
    ) {}
}
