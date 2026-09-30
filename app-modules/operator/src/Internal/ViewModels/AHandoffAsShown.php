<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * Where one person's hand-off stands, as the fields a screen draws.
 *
 * Every sentence is the stack's. What to do next arrives as which of this
 * app's own ways of doing it to offer, at most one of them.
 */
final readonly class AHandoffAsShown
{
    /**
     * @param string                      $heading   the catalogue key for where it stands, or empty where the reason says it
     * @param string                      $reason    why it stands there, or empty
     * @param bool                        $asksAgain whether asking again is offered: what there is to do next, or nothing else is
     * @param bool                        $invites   whether inviting them is what there is to do next
     * @param bool                        $starts    whether starting the media server is
     * @param bool                        $records   whether recording the address is
     * @param bool                        $handsOver whether there is a code to hand over
     * @param list<list<bool>>            $squares   the address as a code, rows of squares dark where true, or none where it could not be drawn
     * @param string                      $address   the address the code carries, or empty
     * @param string                      $caution   what is worth knowing about that address, or empty
     * @param list<string>                $steps     how they sign in on their device, in order
     * @param list<AClientAsShown>        $clients   every app a device can be pointed at the server with
     * @param list<ASignedInDeviceAsShown> $signedIn every device signed in to the account now
     * @param AgoAsShown                 $given     how long ago the code was first given, saying nothing where it has not been or would not be dated
     */
    public function __construct(
        public string $heading,
        public string $reason,
        public bool $asksAgain,
        public bool $invites,
        public bool $starts,
        public bool $records,
        public bool $handsOver,
        public array $squares,
        public string $address,
        public string $caution,
        public array $steps,
        public array $clients,
        public array $signedIn,
        public AgoAsShown $given,
    ) {}
}
