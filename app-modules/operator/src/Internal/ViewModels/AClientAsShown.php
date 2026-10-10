<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One app a device can be pointed at the server with, as the fields a screen draws.
 */
final readonly class AClientAsShown
{
    /**
     * @param string $device     what somebody would call the device
     * @param string $client     what to use on it
     * @param bool   $openSource whether that app is open source
     * @param string           $link       the link that opens it at this server, or empty where the address alone is its code
     * @param list<list<bool>> $squares    that link as a code, rows of squares dark where true, or none where there is no link or it could not be drawn
     */
    public function __construct(
        public string $device,
        public string $client,
        public bool $openSource,
        public string $link,
        public array $squares,
    ) {}
}
