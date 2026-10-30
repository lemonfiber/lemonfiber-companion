<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One value a setting accepts, flattened for a template. */
final readonly class AChoiceAsShown
{
    /**
     * @param string           $value   the value, as the stack wrote it
     * @param bool             $isOpen  whether it is a choice now
     * @param list<ALineToSay> $needs   each setting it needs, with the values that setting has to hold one of
     * @param list<string>     $hangsOn each setting it needs, by its name
     */
    public function __construct(
        public string $value,
        public bool $isOpen,
        public array $needs,
        public array $hangsOn,
    ) {}
}
