<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One check a run would run that disturbs something, flattened for a template.
 */
final readonly class ADisturbanceAsShown
{
    /**
     * @param string   $title     the finding's title, in the stack's words
     * @param string   $disturbs  what checking it disturbs, in the stack's words
     * @param string   $boundSaid the catalogue key for the stack's bound, or for its having given none
     * @param int|null $seconds   the bound in whole seconds, where the stack gave one
     */
    public function __construct(
        public string $title,
        public string $disturbs,
        public string $boundSaid,
        public ?int $seconds,
    ) {}
}
