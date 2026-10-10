<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function count;
use function explode;

use InvalidArgumentException;

use function is_array;
use function parse_url;

use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

use function preg_match;
use function rawurldecode;

/**
 * An invitation's join link, read strictly: where the house is, the
 * certificate it presents, which house it is, when the offer lapses, and the
 * name the person signs in as.
 *
 * **It is not pairing material.** It adds a house to a phone holding
 * none, pinned to the fingerprint it names, and it never replaces a pin a
 * phone already holds: a link anybody can forward is not a way to re-pin a
 * house. What it can lead to is the one member account it names.
 *
 * Refused whole, with {@see JoinLinkCannotBeUsed}, where a parameter is
 * missing, one is not a join link's, one cannot be read, or the offer has
 * lapsed.
 */
final readonly class AJoinLink
{
    /** Where every join link points, before its parameters. */
    public const string AT = 'lemonfiber://join';

    /** The parts a join link is written with, and no others. */
    private const array PARTS = ['host', 'query', 'scheme'];

    /** A moment as a link writes it: seconds since the epoch, in few enough digits to stay inside an integer. */
    private const string A_MOMENT = '/\A\d{1,18}\z/';

    private function __construct(
        private Address $at,
        private Fingerprint $presenting,
        private StackId $stack,
        private AMembersName $name,
        private bool $claims,
    ) {}

    /**
     * The one place what was handed over becomes a join link.
     *
     * **No `@throws`, deliberately**, for the reason {@see Pairing::read()}
     * gives; the refusal is {@see JoinLinkCannotBeUsed}.
     */
    public static function read(string $handed, Clock $clock): self
    {
        $said = self::parameters(self::query($handed));

        foreach (WhatAJoinLinkSays::cases() as $parameter) {
            if ($parameter->isAlwaysCarried() && ! array_key_exists($parameter->value, $said)) {
                throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithoutAParameter, $parameter->value);
            }
        }

        self::stillOpen($said[WhatAJoinLinkSays::Expires->value], $clock);

        try {
            return new self(
                Address::joinedAt($said[WhatAJoinLinkSays::Address->value]),
                Fingerprint::of($said[WhatAJoinLinkSays::Fingerprint->value]),
                StackId::saidBy($said[WhatAJoinLinkSays::Stack->value]),
                AMembersName::of($said[WhatAJoinLinkSays::Name->value]),
                self::claimIn($said),
            );
        } catch (JoinLinkCannotBeUsed $refused) {
            throw $refused;
        } catch (InvalidArgumentException $unread) {
            throw JoinLinkCannotBeUsed::unreadable($unread);
        }
    }

    /** The scheme every join link is written under, which is the one the platform opens this app at. */
    public static function scheme(): string
    {
        return parse_url(self::AT, PHP_URL_SCHEME);
    }

    /** The house it names, under what this phone calls it, pinned to the certificate the link names. */
    public function house(StackName $called): Stack
    {
        return Stack::of($this->stack, $called, $this->at, $this->presenting);
    }

    /** Which house it names. */
    public function stack(): StackId
    {
        return $this->stack;
    }

    /** The name the person signs in as. */
    public function name(): AMembersName
    {
        return $this->name;
    }

    /** Whether it carries a claim: the person chooses their password, rather than signing in with one. */
    public function claims(): bool
    {
        return $this->claims;
    }

    /** The query of a join link, or a refusal where what was handed over is not one. */
    private static function query(string $handed): string
    {
        $parts = parse_url($handed);

        if (! is_array($parts) || ! array_key_exists('scheme', $parts) || ! array_key_exists('host', $parts)
            || $parts['scheme'] !== self::scheme() || $parts['host'] !== parse_url(self::AT, PHP_URL_HOST)) {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::NotAJoinLink);
        }

        if (! array_key_exists('query', $parts)) {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithoutAParameter);
        }

        if (array_diff(array_keys($parts), self::PARTS) !== []) {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::NotAJoinLink);
        }

        return $parts['query'];
    }

    /**
     * Each parameter by its name, decoded; one written twice, or without a value, cannot be read.
     *
     * @return array<string, string>
     */
    private static function parameters(string $query): array
    {
        $said = [];

        foreach (explode('&', $query) as $pair) {
            $halves = explode('=', $pair, 2);

            if (count($halves) !== 2) {
                throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead);
            }

            [$written, $value] = $halves;
            $name = rawurldecode($written);

            if (WhatAJoinLinkSays::tryFrom($name) === null) {
                throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithAParameterItDoesNotKnow);
            }

            if (array_key_exists($name, $said)) {
                throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead, $name);
            }

            $said[$name] = rawurldecode($value);
        }

        return $said;
    }

    /** Refuses a link whose offer has lapsed; lapsing at this very moment counts, as it does for pairing material. */
    private static function stillOpen(string $expires, Clock $clock): void
    {
        if (preg_match(self::A_MOMENT, $expires) !== 1) {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead, WhatAJoinLinkSays::Expires->value);
        }

        if (! $clock->now()->isBefore(Instant::atEpochSeconds((int) $expires))) {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::Lapsed);
        }
    }

    /**
     * Whether the link carries a claim; one carried empty cannot be read.
     *
     * @param array<string, string> $said
     */
    private static function claimIn(array $said): bool
    {
        if (! array_key_exists(WhatAJoinLinkSays::Claim->value, $said)) {
            return false;
        }

        if ($said[WhatAJoinLinkSays::Claim->value] === '') {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead, WhatAJoinLinkSays::Claim->value);
        }

        return true;
    }
}
