<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of the support bundle asked for from this screen, flattened for a template.
 *
 * The bundle is empty until the stack has answered with one, and the refusal
 * is empty unless the stack refused, so a template cannot draw either off a
 * bundle still being gathered.
 */
final readonly class HowTheBundleWent
{
    /**
     * @param HowTheReadingWent $went      whether asking after it came back, and what stood in the way where it did not
     * @param bool              $wasAsked  whether a bundle was asked for here, so there is anything to follow
     * @param bool              $isWorking whether the stack is still gathering it
     * @param bool              $hasEnded  whether the stack has no outcome for it any more
     * @param string            $refused   what the stack refused the bundle with, in its own words, blank unless it refused
     * @param bool              $isWritten whether the bundle exists on the machine, rather than being described
     * @param ?ABundleAsShown   $bundle    the bundle, once the stack answered with one
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $wasAsked,
        public bool $isWorking,
        public bool $hasEnded,
        public string $refused,
        public bool $isWritten,
        public ?ABundleAsShown $bundle,
    ) {}
}
