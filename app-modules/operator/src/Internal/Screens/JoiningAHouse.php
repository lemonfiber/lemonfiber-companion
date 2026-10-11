<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\Remembering;
use Modules\Kernel\Api\AClaim;
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
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
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

    /** What the house said, in the household's words, where it refused and said something; this app's own lines stand in where it did not. */
    public ?WhatTheHouseholdWasTold $told = null;

    /** Where the house a join link would add is, as the link carries it, while the person is asked whether somebody in their house sent it. */
    public string $offeredAt = '';

    /** Whether the person chooses their password, with the claim their invitation carries, rather than signing in with one. */
    public bool $choosing = false;

    /** Whether the name in the field is the one a join link named, drawn as a field the person cannot type into; the link's own name is what is offered. */
    public bool $nameIsTheLinks = false;

    /** The house a join link would add, held until the person says somebody in their house sent it; nothing is pinned or kept meanwhile. */
    private ?Stack $offered = null;

    /** The link that offered the house, whose name and claim the person goes on with once they say so. */
    private ?AJoinLink $offeredBy = null;

    /** The claim the person chooses their password with, while they do; never kept past this screen. */
    private ?AClaim $claim = null;

    /** The one account a join link names, which is the only one it may lead to; nothing where the house was found by a code. */
    private ?AMembersName $namedByTheLink = null;

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
        $this->namedByTheLink = null;
        $this->nameIsTheLinks = false;
        $this->forgetTheHouse();

        $this->readACode($this->camera, function (string $handed): void {
            $this->handed($handed);
        });
    }

    /** Add the house a join link offered, now the person has said somebody in their house sent it, and go on to signing in. */
    public function confirmTheHouse(): void
    {
        $house = $this->offered;
        $link = $this->offeredBy;
        $this->forgetTheHouse();

        if (! $house instanceof Stack || ! $link instanceof AJoinLink) {
            return;
        }

        $this->keeping($house);

        if ($this->at === WhereTheWayInIs::SigningIn) {
            $this->goingOnWith($link);
        }
    }


    /** Let go of the house a join link offered, keeping nothing of it. */
    public function forgetTheHouse(): void
    {
        $this->offered = null;
        $this->offeredBy = null;
        $this->offeredAt = '';
    }

    /** Offer the name and the password, and land on Home once in: the name a join link named where one did, whatever the field holds. */
    public function signIn(): void
    {
        $named = $this->namedByTheLink instanceof AMembersName ? $this->namedByTheLink->forTheExchange() : trim($this->theirName);

        if ($named === '' || $this->joined === '') {
            return;
        }

        $said = Credential::of($this->typed);
        $this->typed = '';
        $this->told = null;
        $stack = $this->around->stack(StackId::rememberedAs($this->joined));

        $offered = $this->claim instanceof AClaim
            ? $this->admitting->claimAs($stack, AMembersName::of($named), $said, $this->claim)
            : $this->admitting->admitAs($stack, AMembersName::of($named), $said);

        $this->went = $offered->either(
            opened: fn(Session $session, Instant $until, Whose $whose): HowTheSignInWent => $this->sessionKept($stack->id(), $session, $whose),
            refused: function (Obstacle $why): HowTheSignInWent {
                $this->told = $why->whatTheHouseholdWasTold();

                return HowTheSignInWent::metWithAName($why);
            },
        );

        if ($this->went === HowTheSignInWent::InvitationWasNotOpen) {
            $this->signingInAs($named);
        }

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
     * The house a join link names: offered for the person to confirm where the phone holds none, signed in to where it holds it, and refused where it holds it under another certificate.
     *
     * **A link anybody can send never adds a house on its own.** It names an
     * address and a certificate, and whoever wrote it chose both; added
     * straight away, a stranger's link would have the person typing their
     * password into the stranger's machine. So nothing is pinned, kept or sent
     * until they say somebody in their house sent it.
     *
     * A link carrying a claim has the person choose their password with it,
     * and one carrying none has them sign in with the one they were given.
     */
    private function openedBy(AJoinLink $link): self
    {
        $house = $link->house($this->called());

        return $this->admits($house) ? $this->offering($house, $link) : $this;
    }

    /** Signing in to a house this phone holds under the link's certificate, or the person asked whether to trust one it does not hold. */
    private function offering(Stack $house, AJoinLink $link): self
    {
        if ($this->stacks->configured()->knows($house->id())) {
            return $this->signingInTo($house->id())->goingOnWith($link);
        }

        $this->offered = $house;
        $this->offeredBy = $link;
        $this->offeredAt = $house->at()->forThePersonAskedToTrustIt();

        return $this;
    }

    /** The link's name in the field, and choosing a password with its claim where it carries one, or signing in where it carries none. */
    private function goingOnWith(AJoinLink $link): self
    {
        $this->namedByTheLink = $link->name();
        $this->nameIsTheLinks = true;
        $named = $link->name()->forTheExchange();

        return $link->leadsTo(
            claiming: fn(AClaim $claim): self => $this->choosingWith($named, $claim),
            signingIn: fn(): self => $this->signingInAs($named),
        );
    }

    /** The name in the field, with the person choosing their password with the claim. */
    private function choosingWith(string $named, AClaim $claim): self
    {
        $this->theirName = $named;
        $this->claim = $claim;
        $this->choosing = true;

        return $this;
    }

    /** The name in the field, with the person signing in with the password they were given, and no claim held. */
    private function signingInAs(string $named): self
    {
        $this->theirName = $named;
        $this->claim = null;
        $this->choosing = false;

        return $this;
    }

    /**
     * Whether the house may be joined as handed over: never where this phone holds it under another certificate.
     *
     * The one gate every way in passes, a link and a code alike: a phone
     * somebody was invited on never has a house it holds re-pinned, so what it
     * was handed cannot point a house it already trusts at another key.
     */
    private function admits(Stack $house): bool
    {
        if ($this->stacks->configured()->wouldRepin($house)) {
            $this->met = WhatFindingTheHouseMet::NotThisHouse;

            return false;
        }

        return true;
    }

    /** What the house is called on this phone. */
    private function called(): StackName
    {
        $called = $this->catalogue->get(self::CALLED);

        return StackName::of(is_string($called) ? $called : self::CALLED);
    }

    /** Keep the house, going on to signing in where it was kept; one held under another certificate is refused and nothing is written. */
    private function keeping(Stack $house): self
    {
        if (! $this->admits($house)) {
            return $this;
        }

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
