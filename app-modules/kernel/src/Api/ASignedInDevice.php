<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One device the media server lists as signed in to the account, with when it last heard from it.
 */
final readonly class ASignedInDevice
{
    private function __construct(private string $device, private string $client, private AMomentAsWritten $lastSeen) {}

    /** As the media server listed it; a blank device or app is refused. */
    public static function listed(string $device, string $client, AMomentAsWritten $lastSeen): self
    {
        foreach (['device' => $device, 'client' => $client] as $field => $said) {
            if (trim($said) === '') {
                throw HandoffSaysNothing::about($field);
            }
        }

        return new self($device, $client, $lastSeen);
    }

    /** What the device calls itself. */
    public function device(): string
    {
        return $this->device;
    }

    /** The app it signed in with. */
    public function client(): string
    {
        return $this->client;
    }

    /** When the media server last heard from it, as it wrote it; unreadable where it did not say. */
    public function lastSeen(): AMomentAsWritten
    {
        return $this->lastSeen;
    }
}
