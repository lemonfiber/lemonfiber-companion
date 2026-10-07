<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Networking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheLocalNetwork;
use Psr\Log\LoggerInterface;

use function sprintf;

use Throwable;

/**
 * {@see PinnedClients}, with the phone asked why a stack was silent.
 *
 * A switched-off machine, a phone with no network and a platform that refused
 * this app the local network all reach the socket as the same silence, and
 * their remedies are opposite: check the machine, turn the network on, allow
 * the app in Settings. The reach cannot tell them apart; the phone can.
 *
 * **Asked in that order, and only after the silence.** No network at all is
 * the first question, because a phone with none has no local network to refuse.
 * A platform that refused the app comes next. Silence that neither explains is
 * whichever silence the reach says it was. A reach that was answered asks the phone nothing, and an
 * envelope in another API version is never the phone's.
 *
 * **It opens nothing.** The client comes from {@see PinnedClients}, pinned, and
 * is handed straight on, so the one file that can open a connection is still
 * one.
 *
 * **Silence is noted for a developer, without the address.** The note names
 * the way the SDK says nothing answered, the port, and which kind of
 * name the address is, and never the address: it is where somebody lives. It
 * goes to the log a debug build keeps, and nowhere in any other build, where
 * the composition root hands this a log that keeps nothing.
 */
final readonly class ClientsThatAskTheDevice implements Clients
{
    /** The note a silent reach leaves: what was raised, which way nothing answered, the port, and which kind of address was tried. */
    private const string NOTED = 'reach: nothing answered (%s), %s, port %d, address %s';

    public function __construct(
        private PinnedClients $pinned,
        private Networking $network,
        private TheLocalNetwork $localNetwork,
        private LoggerInterface $notes,
    ) {}

    public function client(Stack $stack, Session $session): Client
    {
        return $this->pinned->client($stack, $session);
    }

    public function whatStoodInTheWay(Stack $stack, Throwable $why): Obstacle
    {
        if ($why instanceof Unreachable) {
            $this->notes->debug(sprintf(
                self::NOTED,
                $why::class,
                $why->why()->value,
                $stack->at()->port(),
                $stack->at()->howItIsWritten()->value,
            ));
        }

        if ($why instanceof Unreachable && ! $this->network->isConnected()) {
            return Obstacle::of(KindOfObstacle::DeviceHasNoNetwork);
        }

        if ($why instanceof Unreachable && $this->localNetwork->refusesTheWayTo($stack->at())) {
            return Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted);
        }

        return $this->pinned->whatStoodInTheWay($stack, $why);
    }
}
