<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The finding about one check, and what running it would disturb where running it disturbs anything. */
final readonly class TheCheckAsFound
{
    /**
     * @param string                $title    what the check is called, in the stack's words
     * @param string                $verdict  the catalogue key for how the check stands now
     * @param string                $meaning  what the stack says about it, or empty
     * @param ARunThatDisturbs|null $disturbs what running it would disturb, or null where running it disturbs nothing
     */
    public function __construct(
        public string $title,
        public string $verdict,
        public string $meaning,
        public ?ARunThatDisturbs $disturbs,
    ) {}
}
