<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One request lemonfiber makes on its own account, flattened for a template.
 *
 * Every text field is always set, because the value it comes from refuses to
 * be built without it. The destinations may be empty, and the template draws
 * that as *nowhere is configured* — which is not *switched off*, and the two
 * are separate fields here so neither can be drawn as the other.
 */
final readonly class OneOfOurRequests
{
    /**
     * @param string       $asksForSaid  the catalogue key for which request this is
     * @param list<string> $destinations where it goes as the machine is configured, possibly nowhere
     * @param string       $purpose      why lemonfiber asks
     * @param string       $sends        exactly what travels
     * @param string       $allowedSaid  the catalogue key for whether it may go out
     * @param string       $switch       the setting that switches it off
     * @param string       $cost         what stops working once it is off
     */
    public function __construct(
        public string $asksForSaid,
        public array $destinations,
        public string $purpose,
        public string $sends,
        public string $allowedSaid,
        public string $switch,
        public string $cost,
    ) {}
}
