<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KeepingKeysForApps;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Kernel\Api\WhatWasFoundOfTheKeys;
use Modules\Kernel\Api\WhereAKeyStands;

/**
 * A stack where a test says which keys it lists, which revokes by taking a key's name off as revoked, and remembers what it was asked.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatKeepsKeys implements KeepingKeysForApps
{
    private int $listings = 0;

    /** @var list<string> */
    private array $revoked = [];

    private function __construct(private TheKeysForApps|Obstacle $answer, private readonly ARefusalInItsWords|Obstacle|null $revoking = null) {}

    /** A stack listing these keys, revoking any of them when asked. */
    public static function listing(TheKeysForApps $keys): self
    {
        return new self($keys);
    }

    /** A stack listing these keys, refusing every revoke for the reason given. */
    public static function refusingToRevoke(TheKeysForApps $keys, ARefusalInItsWords|Obstacle $why): self
    {
        return new self($keys, $why);
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    public function listedOn(Stack $stack, Session $session): WhatWasFoundOfTheKeys
    {
        $this->listings++;

        return $this->answer instanceof Obstacle ? WhatWasFoundOfTheKeys::met($this->answer) : WhatWasFoundOfTheKeys::found($this->answer);
    }

    public function revokedOn(Stack $stack, Session $session, AKeysName $name): WhatWasFoundOfTheKeys
    {
        if ($this->revoking instanceof Obstacle) {
            return WhatWasFoundOfTheKeys::met($this->revoking);
        }

        if ($this->revoking instanceof ARefusalInItsWords) {
            return WhatWasFoundOfTheKeys::refused($this->revoking);
        }

        if ($this->answer instanceof Obstacle) {
            return WhatWasFoundOfTheKeys::met($this->answer);
        }

        $this->revoked[] = $name->shown();
        $kept = [];

        foreach ($this->answer as $key) {
            $kept[] = $key->name()->shown() === $name->shown() ? $this->revokedNow($key) : $key;
        }

        $this->answer = TheKeysForApps::of($this->answer->purposes(), ...$kept);

        return WhatWasFoundOfTheKeys::found($this->answer);
    }

    /** How many times the keys were listed. */
    public function listings(): int
    {
        return $this->listings;
    }

    /**
     * The names revoked, in order.
     *
     * @return list<string>
     */
    public function revoked(): array
    {
        return $this->revoked;
    }

    private function revokedNow(AKeyForAnApp $key): AKeyForAnApp
    {
        return AKeyForAnApp::listed($key->name(), $key->admits(), $key->isFor(), WhereAKeyStands::Revoked, $key->minted(), $key->used(), $key->wasMintedByAMember());
    }
}
