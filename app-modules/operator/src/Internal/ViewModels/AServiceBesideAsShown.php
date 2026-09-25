<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One service the household can reach that is not the front door, flattened for a template.
 */
final readonly class AServiceBesideAsShown
{
    /**
     * @param string $service    the service, by the name it shows itself under
     * @param string $facingSaid the catalogue key for what it is to the household
     * @param string $because    why it is not somewhere to begin
     * @param string $url        the address as the stack sent it, or empty
     * @param string $caution    what is worth knowing about that address, or empty
     */
    public function __construct(
        public string $service,
        public string $facingSaid,
        public string $because,
        public string $url,
        public string $caution,
    ) {}
}
