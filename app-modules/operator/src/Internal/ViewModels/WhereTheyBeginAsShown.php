<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The service the household begins at and where it is reached, flattened for a template.
 *
 * Every field is empty where the stack publishes nothing they could begin at,
 * and `url` and `caution` are empty where it names a service and no address.
 * `url` is the stack's text, never one this app composed.
 */
final readonly class WhereTheyBeginAsShown
{
    /**
     * @param string $service    the service, by the name it shows itself under, or empty
     * @param string $facingSaid the catalogue key for what it is to them, or empty
     * @param string $url        the address as the stack sent it, or empty
     * @param string $caution    what is worth knowing about that address, or empty
     */
    public function __construct(
        public string $service,
        public string $facingSaid,
        public string $url,
        public string $caution,
    ) {}

    /** Nothing to begin at. */
    public static function nowhere(): self
    {
        return new self(service: '', facingSaid: '', url: '', caution: '');
    }
}
