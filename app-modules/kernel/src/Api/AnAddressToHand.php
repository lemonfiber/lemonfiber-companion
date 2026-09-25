<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * An address to hand somebody in the household, exactly as the stack sent it, or none.
 *
 * **Never built here.** The stack reads it off the machine at the moment of
 * asking, and this holds that text and nothing derived from it: an address
 * this app put together from a host and a port would be one nobody checked
 * answers. None at all is a door the machine will not say how to reach.
 *
 * `caution` is empty where the address keeps working on its own.
 */
final readonly class AnAddressToHand
{
    private function __construct(private string $url, private string $caution) {}

    /** The address the stack sent, with what is worth knowing about it; a blank one is refused. */
    public static function at(string $url, string $caution): self
    {
        if (trim($url) === '') {
            throw TheDoorSaysNothing::about('url');
        }

        if ($caution !== '' && trim($caution) === '') {
            throw TheDoorSaysNothing::about('caution');
        }

        return new self($url, $caution);
    }

    /** The stack sent no address. */
    public static function none(): self
    {
        return new self('', '');
    }

    /** The whole address, as it would be typed or followed, or empty where there is none. */
    public function url(): string
    {
        return $this->url;
    }

    /** What is worth knowing about the address itself, or empty. */
    public function caution(): string
    {
        return $this->caution;
    }
}
