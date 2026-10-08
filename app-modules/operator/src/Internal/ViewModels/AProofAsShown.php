<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One proof, with what asking it came to and what the stack said with it. */
final readonly class AProofAsShown
{
    /**
     * @param string       $establishes what it establishes
     * @param string       $asks        the method and path it asks
     * @param string       $why         why it is worth asserting, or empty
     * @param string       $cameToSaid  what it came to, as a catalogue key
     * @param list<string> $said        each fault, the reason it was unproven, or each declared failure
     */
    public function __construct(
        public string $establishes,
        public string $asks,
        public string $why,
        public string $cameToSaid,
        public array $said,
    ) {}
}
