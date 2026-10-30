<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\ServiceId;

/** A service the stack holds back until the operator gives what it needs to start, flattened for a template. */
final readonly class AServiceWaitingAsShown
{
    /**
     * @param ServiceId        $id      the service, to reach its screen by
     * @param string           $service the service, by its name
     * @param list<ALineToSay> $wants   each thing it waits for, never with its value
     */
    public function __construct(
        public ServiceId $id,
        public string $service,
        public array $wants,
    ) {}
}
