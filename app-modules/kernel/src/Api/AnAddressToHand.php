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
 * `caution` is empty where the address keeps working on its own. What a code
 * of it carries is the address alone, never its caution.
 */
final readonly class AnAddressToHand
{
    private function __construct(private string $url, private string $caution, private string $decline = '') {}

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

    /**
     * The address the stack sent, with its caution and the address it gave for
     * turning the invitation down; a blank one is refused.
     *
     * The decline address is handed over in the text beside the address, and
     * never in a code of it.
     */
    public static function declinable(string $url, string $caution, string $decline): self
    {
        if (trim($decline) === '') {
            throw TheDoorSaysNothing::about('decline');
        }

        $address = self::at($url, $caution);

        return new self($address->url, $address->caution, $decline);
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

    /** What a code of it carries, which is the address and never its caution. */
    public function carried(): string
    {
        return $this->url;
    }

    /** What is worth knowing about the address itself, or empty. */
    public function caution(): string
    {
        return $this->caution;
    }

    /** The address that turns the invitation down, or empty where the stack gave none. */
    public function decline(): string
    {
        return $this->decline;
    }

    /** The address that turns the invitation down, as an address to hand over in its own right, or none where the stack gave none. */
    public function declining(): self
    {
        return new self($this->decline, '');
    }
}
