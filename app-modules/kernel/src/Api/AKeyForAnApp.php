<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One integration key a stack lists: its name, what it admits and is for, where it stands, and when.
 *
 * **There is no secret here, and nowhere to put one.** The listing carries none.
 */
final readonly class AKeyForAnApp
{
    private function __construct(
        private AKeysName $name,
        private WhatAKeyAdmits $admits,
        private WhatAKeyIsFor $for,
        private WhereAKeyStands $stands,
        private AMomentAsWritten $minted,
        private AMomentAsWritten $used,
        private bool $memberMinted,
    ) {}

    /** What the stack listed of one key. */
    public static function listed(
        AKeysName $name,
        WhatAKeyAdmits $admits,
        WhatAKeyIsFor $for,
        WhereAKeyStands $stands,
        AMomentAsWritten $minted,
        AMomentAsWritten $used,
        bool $memberMinted,
    ): self {
        return new self($name, $admits, $for, $stands, $minted, $used, $memberMinted);
    }

    /** The name it was minted under, which revoking it names. */
    public function name(): AKeysName
    {
        return $this->name;
    }

    public function admits(): WhatAKeyAdmits
    {
        return $this->admits;
    }

    public function isFor(): WhatAKeyIsFor
    {
        return $this->for;
    }

    public function stands(): WhereAKeyStands
    {
        return $this->stands;
    }

    public function minted(): AMomentAsWritten
    {
        return $this->minted;
    }

    /** When it was last admitted; unreadable where it never has been. */
    public function used(): AMomentAsWritten
    {
        return $this->used;
    }

    /** Whether a household member minted it for themselves. */
    public function wasMintedByAMember(): bool
    {
        return $this->memberMinted;
    }
}
