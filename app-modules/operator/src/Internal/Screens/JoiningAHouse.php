<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\Remembering;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\AJoinLink;
use Modules\Kernel\Api\AMembersName;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Modules\Operator\Internal\HasAWayBack;
use Modules\Operator\Internal\HowAnInvitedPhoneFindsTheHouse;
use Modules\Operator\Internal\InTheWayInsWords;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\WhatFindingTheHouseMet;
use Modules\Operator\Internal\WhatStoodInTheWayOfJoining;
use Modules\Operator\Internal\WhereTheWayInIs;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\Screens\WaitsAFrameForWhatTheStackServes;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function parse_url;

use const PHP_URL_QUERY;

use function sprintf;
use function trim;

/**
 * The member's own way in: find the house, sign in, and land on Home.
 *
 * Three steps, each saying which it is, in words that name no part of the
 * system. The phone is handed the house as {@see HowAnInvitedPhoneFindsTheHouse}
 * says, which is the one place a new way of handing it over is added.
 *
 * Pairing and signing in are the ones the operator's screens use, through the
 * same ports, so a member's phone is introduced to the house and holds its
 * session exactly as the operator's does. What differs is only what it says.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class JoiningAHouse extends NativeComponent
{
    use ReadsACode;
    use HasAWayBack;
    use OffersTheAppsSettings;
    use DrawsItsTemplate;
    use WaitsAFrameForWhatTheStackServes;

    public const string TEMPLATE = 'operator::joining-a-house';

    /** How this phone finds the house. */
    private const HowAnInvitedPhoneFindsTheHouse FINDS_THE_HOUSE = HowAnInvitedPhoneFindsTheHouse::YourInvitation;

    /** What the house is called on a phone that joined it, until somebody renames it. */
    private const string CALLED = 'household.joining.called';

    /** Which step this phone is on. */
    public WhereTheWayInIs $at = WhereTheWayInIs::FindingTheHouse;

    /** The house this phone joined, by its stored identifier, once it has. */
    public string $joined = '';

    /** What stood in the way of finding the house, once something did. */
    public ?WhatFindingTheHouseMet $met = null;

    /** The member's name, as it stands in the field. */
    public string $theirName = '';

    /** The password, as it stands in the field; offered once and cleared. */
    public string $typed = '';

    /** What signing in came to, once it was tried. */
    public HowTheSignInWent $went = HowTheSignInWent::NotYet;

    public function __construct(
        private readonly Scanning $camera,
        private readonly Introducing $introducing,
        private readonly Remembering $remembering,
        private readonly Stacks $stacks,
        private readonly Clock $clock,
        private readonly Admitting $admitting,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        private readonly Translator $catalogue,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** How this phone finds the house, which the first step says. */
    public function findsTheHouse(): HowAnInvitedPhoneFindsTheHouse
    {
        return self::FINDS_THE_HOUSE;
    }

    /** Open the house a join link names where the phone opened this screen at one. */
    public function mount(): void
    {
        $opened = (string) parse_url($this->nativeRouter?->currentUri() ?? '', PHP_URL_QUERY);

        if ($opened !== '') {
            $this->handed(sprintf('%s?%s', AJoinLink::AT, $opened));
        }
    }

    /** Open the camera for the code, and find the house it names. */
    public function findTheHouse(): void
    {
        $this->met = null;

        $this->readACode($this->camera, function (string $handed): void {
            $this->handed($handed);
        });
    }

    /** Offer the name and the password, and land on Home once in. */
    public function signIn(): void
    {
        $named = trim($this->theirName);

        if ($named === '' || $this->joined === '') {
            return;
        }

        $said = Credential::of($this->typed);
        $this->typed = '';
        $stack = $this->around->stack(StackId::rememberedAs($this->joined));

        $this->went = $this->admitting->admitAs($stack, AMembersName::of($named), $said)->either(
            opened: fn(Session $session, Instant $until, Whose $whose): HowTheSignInWent => $this->sessionKept($stack->id(), $session, $whose),
            refused: static fn(Obstacle $why): HowTheSignInWent => HowTheSignInWent::metWithAName($why),
        );

        if ($this->went->isSignedIn()) {
            $this->replaceTheWholeStack(AStacksScreen::Shelf->forTheStack($stack->id()));
        }
    }

    /** What stood in the way of signing in, or null where nothing did. */
    public function stoodInTheWay(): ?WhatStoodInTheWayOfJoining
    {
        return WhatStoodInTheWayOfJoining::of($this->went);
    }

    /** The key for what to do about the camera giving nothing back, in the household's words, or empty where it gave something. */
    public function whatToDoAboutTheCamera(): string
    {
        return $this->nothingCameBack instanceof WhyNothingWasScanned ? InTheWayInsWords::remedy($this->nothingCameBack->value) : '';
    }

    /** Read what the phone was handed, and join the house it names. */
    private function handed(string $handed): void
    {
        self::FINDS_THE_HOUSE->read($handed, $this->clock)->either(
            link: $this->openedBy(...),
            code: fn(Pairing $pairing): self => $this->keeping($this->introducing->stack($pairing, $this->called())),
            refused: function (WhatFindingTheHouseMet $met): self {
                $this->met = $met;

                return $this;
            },
        );
    }

    /**
     * The house a join link names, added where the phone holds none and refused where it holds it under another certificate.
     *
     * A link carrying a claim asks the person to choose their password, which
     * this app cannot offer the house yet, so it is refused as one that cannot
     * be used.
     */
    private function openedBy(AJoinLink $link): self
    {
        $house = $link->house($this->called());
        $held = $this->stacks->configured();

        $this->met = match (true) {
            $link->claims() => WhatFindingTheHouseMet::LinkUnusable,
            $held->wouldRepin($house) => WhatFindingTheHouseMet::NotThisHouse,
            default => null,
        };

        if ($this->met instanceof WhatFindingTheHouseMet) {
            return $this;
        }

        $this->theirName = $link->name()->forTheExchange();

        return $held->knows($house->id()) ? $this->signingInTo($house->id()) : $this->keeping($house);
    }

    /** What the house is called on this phone. */
    private function called(): StackName
    {
        $called = $this->catalogue->get(self::CALLED);

        return StackName::of(is_string($called) ? $called : self::CALLED);
    }

    /** Keep the house, going on to signing in where it was kept. */
    private function keeping(Stack $house): self
    {
        if (! $this->remembering->stack($house)->isPaired()) {
            $this->met = WhatFindingTheHouseMet::NotKept;

            return $this;
        }

        return $this->signingInTo($house->id());
    }

    /** Go on to signing in to the house held. */
    private function signingInTo(StackId $house): self
    {
        $this->joined = $house->stored();
        $this->at = WhereTheWayInIs::SigningIn;

        return $this;
    }

    /** Keep the session this phone was given, saying whether that happened. */
    private function sessionKept(StackId $stack, Session $session, Whose $whose): HowTheSignInWent
    {
        return $this->storage->keep($stack, $session, $whose)->either(
            kept: static fn(): HowTheSignInWent => HowTheSignInWent::SignedIn,
            refused: static fn(WhySessionCannotBeKept $why): HowTheSignInWent => HowTheSignInWent::unkept($why),
        );
    }
}
