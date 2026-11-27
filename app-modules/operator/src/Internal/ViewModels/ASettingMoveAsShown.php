<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One setting a change moves, flattened for a template. */
final readonly class ASettingMoveAsShown
{
    /**
     * @param ALineToSay                     $moves       what it is to hold, or what it falls back to where it goes back to its default
     * @param ALineToSay                     $was         what it held before, or the default it was at
     * @param list<AServiceReadingItAsShown> $readers     each reader whose needs are met with its own default, where it goes back to each reader's own
     * @param string                         $consequence what moving it costs, in the stack's words, blank where nothing
     */
    public function __construct(
        public ALineToSay $moves,
        public ALineToSay $was,
        public array $readers,
        public string $consequence,
    ) {}
}
