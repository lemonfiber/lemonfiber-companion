<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KeepingPhoneKeys;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThePhoneKeys;
use Modules\Kernel\Api\WhatWasFoundOfThePhoneKeys;

/**
 * A stack listing phone keys, revoking one by name when asked, or refusing.
 *
 * Held to the same assertions as the adapter by `KeepingPhoneKeysContractTest`.
 */
final class AStackThatKeepsPhoneKeys implements KeepingPhoneKeys
{
    /** When a key this stack revokes was revoked, as the stack writes an instant. */
    public const string REVOKED_AT = '2026-10-13T10:00:00Z';

    /** @var list<string> */
    private array $revoked = [];

    private function __construct(private ThePhoneKeys|Obstacle $answer, private readonly ?ARefusalInItsWords $refusing = null) {}

    /** A stack listing these phone keys, revoking any of them when asked. */
    public static function listing(ThePhoneKeys $keys): self
    {
        return new self($keys);
    }

    /** A stack listing these phone keys, refusing every revoke in its words. */
    public static function refusingToRevoke(ThePhoneKeys $keys, ARefusalInItsWords $why): self
    {
        return new self($keys, $why);
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    public function listedOn(Stack $stack, Session $session): WhatWasFoundOfThePhoneKeys
    {
        return $this->answer instanceof Obstacle ? WhatWasFoundOfThePhoneKeys::met($this->answer) : WhatWasFoundOfThePhoneKeys::found($this->answer);
    }

    public function revokedOn(Stack $stack, Session $session, APhoneKeysName $name): WhatWasFoundOfThePhoneKeys
    {
        if ($this->answer instanceof Obstacle) {
            return WhatWasFoundOfThePhoneKeys::met($this->answer);
        }

        if ($this->refusing instanceof ARefusalInItsWords) {
            return WhatWasFoundOfThePhoneKeys::refused($this->refusing);
        }

        $this->revoked[] = $name->shown();
        $kept = [];

        foreach ($this->answer as $key) {
            $kept[] = $key->name()->shown() === $name->shown()
                ? APhoneKey::revoked($key->name(), $key->lives(), $key->registered(), $key->used(), AMomentAsWritten::of(self::REVOKED_AT))
                : $key;
        }

        $this->answer = ThePhoneKeys::of(...$kept);

        return WhatWasFoundOfThePhoneKeys::found($this->answer);
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
}
