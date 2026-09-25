<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One kind of device somebody might watch on, the app to use on it, and how well it is served.
 *
 * `caution` and `instead` are empty where the stack said nothing; a blank one
 * is refused.
 */
final readonly class ADeviceToWatchOn
{
    private function __construct(
        private string $device,
        private string $client,
        private HowWellADeviceIsServed $support,
        private string $caution,
        private string $instead,
    ) {}

    /** What the stack recommends for one device. */
    public static function rated(
        string $device,
        string $client,
        HowWellADeviceIsServed $support,
        string $caution,
        string $instead,
    ): self {
        foreach (['device' => $device, 'client' => $client] as $field => $said) {
            if (trim($said) === '') {
                throw AdviceSaysNothing::about($field);
            }
        }

        foreach (['caution' => $caution, 'instead' => $instead] as $field => $said) {
            if ($said !== '' && trim($said) === '') {
                throw AdviceSaysNothing::about($field);
            }
        }

        return new self($device, $client, $support, $caution, $instead);
    }

    /** What somebody would call the device they are holding. */
    public function device(): string
    {
        return $this->device;
    }

    /** What to use on it. */
    public function client(): string
    {
        return $this->client;
    }

    /** How well it is served. */
    public function support(): HowWellADeviceIsServed
    {
        return $this->support;
    }

    /** What is worth knowing before starting, or empty. */
    public function caution(): string
    {
        return $this->caution;
    }

    /** What to do instead where this is a bad device to be stuck with, or empty. */
    public function instead(): string
    {
        return $this->instead;
    }
}
