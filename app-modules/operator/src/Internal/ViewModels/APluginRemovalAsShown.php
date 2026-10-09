<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A removal's account, as the template draws it: what stops and what is left unfilled before the yes, and removed or partly removed after.
 */
final readonly class APluginRemovalAsShown
{
    /**
     * @param string                     $plugin     the plugin it takes off, by its id
     * @param bool                       $isAReading whether nothing was taken, so this is what it would do
     * @param bool                       $agreeable  whether it is a reading with a name a yes can quote, so Remove is offered
     * @param string                     $headline   the catalogue key for how it ended, or empty on a reading
     * @param list<string>               $interrupts every service that stops when it goes
     * @param list<AChangeAndWhyAsShown> $leaves     every capability left unfilled, with what fills it now
     * @param HowPuttingARunBackWent     $wentBack   what putting its changes back came to, or would
     */
    public function __construct(
        public string $plugin,
        public bool $isAReading,
        public bool $agreeable,
        public string $headline,
        public array $interrupts,
        public array $leaves,
        public HowPuttingARunBackWent $wentBack,
    ) {}
}
