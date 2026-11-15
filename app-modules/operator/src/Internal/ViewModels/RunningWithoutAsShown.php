<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * How a stack runs without lemonfiber, as the stack's settings and its home draw it.
 */
final readonly class RunningWithoutAsShown
{
    /**
     * @param HowTheReadingWent         $went           whether the stack answered, and what stopped it where not
     * @param string                    $ownDirectory   the directory of the operator's own stack, empty where it is the bundled one
     * @param string                    $project        the directory the Compose project is written to
     * @param string                    $env            the `.env` file beside it
     * @param string                    $plainUp        the `docker compose` words that bring it up, as typed
     * @param string                    $nonInteractive the words any action runs with without a question
     * @param list<APartLeftOutAsShown> $leftOut        every part those words do not bring up as lemonfiber does
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $ownDirectory,
        public string $project,
        public string $env,
        public string $plainUp,
        public string $nonInteractive,
        public array $leftOut,
    ) {}
}
