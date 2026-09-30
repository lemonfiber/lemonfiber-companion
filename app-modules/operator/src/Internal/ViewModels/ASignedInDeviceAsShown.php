<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * One device signed in to the account, as the fields a screen draws.
 */
final readonly class ASignedInDeviceAsShown
{
    /**
     * @param string      $device what the device calls itself
     * @param string      $client the app it signed in with
     * @param AgoAsShown $seen   how long ago the media server last heard from it, saying nothing where it did not say
     */
    public function __construct(
        public string $device,
        public string $client,
        public AgoAsShown $seen,
    ) {}
}
