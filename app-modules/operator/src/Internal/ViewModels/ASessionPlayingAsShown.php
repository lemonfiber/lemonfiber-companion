<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One session the media server is playing, as its card draws it.
 *
 * `what` and `state` are catalogue keys; `filling` is what fills `what`.
 */
final readonly class ASessionPlayingAsShown
{
    /** @param array<string, string> $filling */
    public function __construct(
        public string $member,
        public string $what,
        public array $filling,
        public string $device,
        public string $state,
    ) {}
}
