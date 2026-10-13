<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\TheMembers;
use Modules\Operator\Internal\MintsFromThisPhone;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\APhoneKeyInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\AStackThatMintsKeys;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;

/**
 * A phone that makes keys for apps on a stack, with everything a screen needs for it in memory.
 *
 * Its own key store holding real P-256 keys, the names its keys are registered under, the stack that
 * mints, who is in the household and the share sheet; each is a field a suite can look into.
 */
final readonly class APhoneThatMints
{
    private function __construct(
        public APhoneKeyInMemory $phone,
        public PhoneKeysNamesKeptInMemory $names,
        public AStackThatMintsKeys $stack,
        public AStackThatInvites $household,
        public AShareSheetThatWasOffered $sheet,
    ) {}

    /** An Android phone with a fingerprint, on a stack that mints, in a household of two who joined and one still invited. */
    public static function onAnAndroidPhone(?APhoneKeyInMemory $phone = null, ?AStackThatMintsKeys $stack = null, ?AShareSheetThatWasOffered $sheet = null, ?AStackThatInvites $household = null, ?PhoneKeysNamesKeptInMemory $names = null): self
    {
        return new self(
            $phone ?? APhoneKeyInMemory::onAnAndroidPhone(),
            $names ?? PhoneKeysNamesKeptInMemory::working(),
            $stack ?? AStackThatMintsKeys::answering(),
            $household ?? AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('ada'), AMember::stillInvited('bo'), AMember::joined('cy'))),
            $sheet ?? AShareSheetThatWasOffered::working(),
        );
    }

    /** The minting a screen is handed, over these. */
    public function minting(): MintsFromThisPhone
    {
        return new MintsFromThisPhone($this->phone, $this->names, $this->stack, $this->household, ACodeOfWhatItWasGiven::working());
    }
}
