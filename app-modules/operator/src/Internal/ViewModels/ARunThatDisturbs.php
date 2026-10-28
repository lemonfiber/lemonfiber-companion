<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Running one check that disturbs something: what it disturbs, for how long, and what a yes runs. */
final readonly class ARunThatDisturbs
{
    /**
     * @param string   $disturbs  what running it disturbs, in the stack's words
     * @param string   $boundSaid the catalogue key for how long the stack bounds the disturbance, or for its giving no bound
     * @param int|null $seconds   the bound, where the stack gives one
     * @param string   $check     the check a yes runs
     * @param string   $only      the check or group a diagnosis is narrowed to, to run it, or empty
     */
    public function __construct(
        public string $disturbs,
        public string $boundSaid,
        public ?int $seconds,
        public string $check,
        public string $only,
    ) {}
}
