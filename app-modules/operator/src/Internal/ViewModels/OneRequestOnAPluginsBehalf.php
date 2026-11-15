<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A request lemonfiber makes on an installed plugin's behalf, flattened for a template.
 */
final readonly class OneRequestOnAPluginsBehalf
{
    /**
     * @param string $plugin      the plugin that declared it, by its id
     * @param string $destination where it goes, as the plugin's manifest named it
     * @param string $purpose     why it may be made
     * @param string $allowedSaid the catalogue key for whether it may go out
     * @param string $switch      the setting that refuses it
     * @param string $cost        what stops working once it is refused
     */
    public function __construct(
        public string $plugin,
        public string $destination,
        public string $purpose,
        public string $allowedSaid,
        public string $switch,
        public string $cost,
    ) {}
}
