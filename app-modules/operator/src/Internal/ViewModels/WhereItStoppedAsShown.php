<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where a walkthrough stopped, flattened for a template, or that it did not. */
final readonly class WhereItStoppedAsShown
{
    /**
     * @param bool         $didStop whether it stopped at all; where it did not, every other field is empty
     * @param string       $step    the step it stopped at, as the stack names it
     * @param string       $whySaid the catalogue key for why it stopped
     * @param string       $remedy  the one thing to try, as the stack said it
     * @param list<string> $logs    what the services were saying, line by line as they came
     */
    public function __construct(
        public bool $didStop,
        public string $step,
        public string $whySaid,
        public string $remedy,
        public array $logs,
    ) {}

    /** It did not stop, so there is nothing to say about where. */
    public static function nowhere(): self
    {
        return new self(didStop: false, step: '', whySaid: '', remedy: '', logs: []);
    }
}
