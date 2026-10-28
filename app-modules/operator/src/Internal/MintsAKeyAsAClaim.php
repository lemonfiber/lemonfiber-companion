<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_key_exists;
use function array_map;
use function intdiv;
use function max;

use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AKeyToMint;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\HowAKeyIsScoped;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Operator\Internal\ViewModels\AKeyClaimAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatMintingShows;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * Making a key for an app from this phone, as a claim, and handing its link over.
 *
 * Offered only where this phone can keep a key in its hardware behind face or fingerprint;
 * elsewhere the screen says why and that a key is made at the stack's terminal. The password is
 * asked only where this phone's key is to be registered, and is let go of the moment it is sent.
 * The link is shown while the screen is: coming back to the screen, from the lock or from
 * anywhere else, finds it gone.
 *
 * The screen that uses it takes a {@see MintsFromThisPhone} as a protected `$mintsFromThisPhone`,
 * a {@see \Modules\Kernel\Api\Sharing} as a protected `$sharing`, a
 * {@see \Modules\Kernel\Api\SecureStorage} as a private `$storage` and a
 * {@see \Modules\Kernel\Api\Clock} as a private `$clock`, and lets go of a session the stack refused.
 *
 * @phpstan-require-extends NativeComponent
 */
trait MintsAKeyAsAClaim
{
    /** Why this phone may not make a key, as a catalogue key, empty where it may, or null before it was asked. */
    public ?string $whyNoMinting = null;

    /** Whether the form for making a key is open. */
    public bool $minting = false;

    /** What the key is to be called. */
    public string $mintName = '';

    /** What it is to admit: `read`, `act` or `member`. */
    public string $mintAdmits = HowAKeyIsScoped::Reading->value;

    /** The member whose own it is, where it admits a member's own. */
    public string $mintMember = '';

    /** What it is for. */
    public string $mintFor = WhatAKeyIsFor::HomeAssistant->value;

    /** The password, typed where this phone's key is to be registered, and emptied once sent. */
    public string $password = '';

    /** @var list<string> the household's members, read once a member's own is chosen */
    public array $mintMembers = [];

    /** What making the key came to, while the screen shows it. */
    public ?WhatMintingShows $minted = null;

    /** The catalogue key for what handing the link over came to, or empty. */
    public string $linkPassedOn = '';

    /** Why making a key is not offered on this phone, as a catalogue key, or empty where it is. */
    public function mintingIsNotOffered(): string
    {
        return $this->whyNoMinting ??= $this->mintsFromThisPhone->whyNot();
    }

    /**
     * What a key may admit, as the words a choice of one taps with.
     *
     * @return list<string>
     */
    public function whatAKeyMayAdmit(): array
    {
        return array_map(static fn(HowAKeyIsScoped $scope): string => $scope->value, HowAKeyIsScoped::cases());
    }

    /**
     * What a key may be for, as the words a choice of one taps with.
     *
     * @return list<string>
     */
    public function whatAKeyMayBeFor(): array
    {
        return array_map(static fn(WhatAKeyIsFor $purpose): string => $purpose->value, WhatAKeyIsFor::cases());
    }

    /** Whether the key is to admit one member's own. */
    public function admitsAMembersOwn(): bool
    {
        return $this->mintAdmits === HowAKeyIsScoped::AMembersOwn->value;
    }

    /** Whether making a key here registers this phone's key first, which asks for the password. */
    public function needsThePassword(): bool
    {
        return ! $this->mintsFromThisPhone->thisPhonesKey($this->stack()->id()) instanceof APhoneKeysName;
    }

    /** The operator asked to make a key. */
    public function startMinting(): void
    {
        $this->minting = $this->mintingIsNotOffered() === '';
        $this->minted = null;
        $this->linkPassedOn = '';
    }

    /** The operator closed the form, or put the link away. */
    public function stopMinting(): void
    {
        $this->minting = false;
        $this->minted = null;
        $this->password = '';
        $this->linkPassedOn = '';
    }

    /** The operator chose what the key admits; a member's own reads who is in the household. */
    public function admitting(string $scope): void
    {
        $chosen = HowAKeyIsScoped::tryFrom($scope);
        $this->mintAdmits = $chosen instanceof HowAKeyIsScoped ? $chosen->value : $this->mintAdmits;

        if ($this->admitsAMembersOwn() && $this->mintMembers === []) {
            $this->mintMembers = $this->membersOfTheHousehold();
        }
    }

    /** The operator chose the member whose own the key is, by row. */
    public function admittingMember(int $which): void
    {
        $this->mintMember = array_key_exists($which, $this->mintMembers) ? $this->mintMembers[$which] : $this->mintMember;
    }

    /** The operator chose what the key is for. */
    public function meantFor(string $purpose): void
    {
        $chosen = WhatAKeyIsFor::tryFrom($purpose);
        $this->mintFor = $chosen instanceof WhatAKeyIsFor ? $chosen->value : $this->mintFor;
    }

    /** Whether everything the key needs has been given. */
    public function mayMint(): bool
    {
        return trim($this->mintName) !== ''
            && (! $this->admitsAMembersOwn() || trim($this->mintMember) !== '')
            && (! $this->needsThePassword() || trim($this->password) !== '');
    }

    /** The operator asked for the key: register this phone's key where it has none, prove the mint, and show the link. */
    public function mint(): void
    {
        if (! $this->mayMint()) {
            return;
        }

        $password = $this->needsThePassword() ? Credential::of($this->password) : null;
        $this->password = '';
        $stack = $this->stack();
        $key = AKeyToMint::of(AKeysName::of(trim($this->mintName)), $this->admitted(), WhatAKeyIsFor::from($this->mintFor));

        $this->minted = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatMintingShows => $this->mintsFromThisPhone->mint($stack, $session, $key, $password, $this->clock->now(), $this->lettingGoIfRefused(
                $stack,
                static fn(Obstacle $why): WhatMintingShows => new WhatMintingShows(claim: null, refused: null, met: HowTheReadingWent::somethingStopped($why), said: null),
            )),
            notHeld: static fn(): WhatMintingShows => new WhatMintingShows(claim: null, refused: null, met: null, said: 'connection.session_has_ended'),
        );
        $this->minting = ! $this->minted->claim instanceof AKeyClaimAsShown;
    }

    /** Hand the link over through the phone's own share sheet. */
    public function passOnTheLink(): void
    {
        $claim = $this->minted?->claim;

        if ($claim === null) {
            return;
        }

        $this->linkPassedOn = $this->sharing->hand(Assembled::as($claim->name, $claim->link))->either(
            over: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('stacks.mint.passed_on'),
            refused: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('stacks.mint.not_passed_on'),
        )->said;
    }

    /** How many whole minutes the link shown still collects the key for; none once it has lapsed. */
    public function minutesTheLinkLasts(): int
    {
        $claim = $this->minted?->claim;

        if ($claim === null) {
            return 0;
        }

        $minute = HowLongAgo::Minutes->seconds();

        return max(0, intdiv($claim->lapsesAt - $this->clock->now()->epochSeconds() + $minute - 1, $minute));
    }

    /** The screen came back to the front: the link it showed is gone. */
    public function onResume(): void
    {
        if ($this->minted?->claim !== null) {
            $this->stopMinting();
        }
    }

    /** What the key is to admit. */
    private function admitted(): WhatAKeyAdmits
    {
        return match (HowAKeyIsScoped::tryFrom($this->mintAdmits)) {
            HowAKeyIsScoped::Acting => WhatAKeyAdmits::acting(),
            HowAKeyIsScoped::AMembersOwn => WhatAKeyAdmits::aMembersOwn($this->mintMember),
            default => WhatAKeyAdmits::reading(),
        };
    }

    /**
     * The names of the household's members who have joined, or none where they could not be read.
     *
     * @return list<string>
     */
    private function membersOfTheHousehold(): array
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): MembersNamed => $this->mintsFromThisPhone->membersOn($stack, $session, $this->lettingGoIfRefused($stack, static fn(): MembersNamed => MembersNamed::none())),
            notHeld: static fn(): MembersNamed => MembersNamed::none(),
        )->names;
    }
}
