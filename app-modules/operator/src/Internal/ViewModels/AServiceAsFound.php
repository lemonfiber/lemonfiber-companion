<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One service already on a machine, flattened for a template. */
final readonly class AServiceAsFound
{
    /**
     * @param string $service       the name it answers to in its project
     * @param string $ports         every host port it publishes, as one line, or empty where it publishes none
     * @param string $runningSaid   the catalogue key for whether it is running
     * @param string $adoptableSaid the catalogue key for whether lemonfiber could take it over
     */
    public function __construct(
        public string $service,
        public string $ports,
        public string $runningSaid,
        public string $adoptableSaid,
    ) {}
}
