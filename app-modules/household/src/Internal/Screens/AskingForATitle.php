<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Closure;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Presenters\HowAFoundTitleReads;
use Modules\Household\Internal\ViewModels\WhatAskingShows;
use Modules\Household\Internal\WhatAFoundTitleIsOpenedWith;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\AskingForTitles;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\FoundTitleSaysNothing;
use Modules\Kernel\Api\HowAnAskWent;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatAnAskCameTo;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * One title a member's search found: what it is, where it stands, and asking for it.
 *
 * **Rehearsed first.** On its first frame it asks the core what asking would
 * come to, which sends nothing, and shows the core's sentence on approval and
 * on what it leaves of their allowance before the member decides.
 *
 * **One action, never hidden.** Ask where the title may be asked for, and
 * otherwise where it stands, drawn as the action and not usable.
 *
 * **Sent once.** The ask carries a key held from the first tap until the core
 * answers, so asking again after no answer is the same ask, and Requests is
 * named as where to look.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class AskingForATitle extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::asking-for-a-title';

    /** What asking would come to, once the frame has asked. */
    public ?WhatAskingShows $rehearsal = null;

    /** What the ask came to, once it was sent. */
    public ?WhatAskingShows $asked = null;

    /** The key the ask is sent under, held until the core answers it. Not shown. */
    public ?string $key = null;

    public function __construct(
        private readonly AskingForTitles $asking,
        private readonly SecureStorage $storage,
        private readonly Entropy $entropy,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** The title, as the search that led here listed it, or none where nothing was carried. */
    public function title(): ?AFoundTitle
    {
        $id = $this->param('service');
        $kind = KindOfTitle::tryFrom($this->carried(WhatAFoundTitleIsOpenedWith::Kind));
        $stands = WhereAFoundTitleStands::tryFrom($this->carried(WhatAFoundTitleIsOpenedWith::Stands));

        if (! is_string($id) || ! $kind instanceof KindOfTitle || ! $stands instanceof WhereAFoundTitleStands) {
            return null;
        }

        $year = $this->carried(WhatAFoundTitleIsOpenedWith::Year);

        try {
            return AFoundTitle::listed($id, $kind, $this->carried(WhatAFoundTitleIsOpenedWith::Title), $year === '' ? WhenItCameOut::unstated() : WhenItCameOut::in((int) $year), $stands);
        } catch (FoundTitleSaysNothing) {
            return null;
        }
    }

    /** The catalogue key for what the title is. */
    public function kindSaid(): string
    {
        return $this->said(static fn(AFoundTitle $title): string => new HowAFoundTitleReads()->kind($title->kind()));
    }

    /** The catalogue key for where the title stands. */
    public function standsSaid(): string
    {
        return $this->said(static fn(AFoundTitle $title): string => new HowAFoundTitleReads()->stands($title->stands()));
    }

    /** The catalogue key for the action drawn where the title cannot be asked for. */
    public function actionSaid(): string
    {
        return $this->said(static fn(AFoundTitle $title): string => new HowAFoundTitleReads()->action($title->stands()));
    }

    /** What asking would come to, asked once per frame where the title may be asked for. */
    public function rehearsal(): WhatAskingShows
    {
        return $this->rehearsal ??= $this->sent(fn(Stack $stack, Session $session, AFoundTitle $title): HowAnAskWent => $this->asking->rehearse($stack, $session, $title));
    }

    /** Ask for the title, under the key held since the first tap. */
    public function ask(): void
    {
        $key = $this->key ??= IdempotencyKey::from($this->entropy->nonce())->sent();
        $this->asked = $this->sent(fn(Stack $stack, Session $session, AFoundTitle $title): HowAnAskWent => $this->asking->ask($stack, $session, $title, IdempotencyKey::of($key)));

        if ($this->asked->cameBack()) {
            $this->key = null;
        }
    }

    /** Ask the core again what asking would come to. */
    public function again(): void
    {
        $this->rehearsal = null;
    }

    /** Where the member's requests are. */
    public function requests(): string
    {
        return $this->goes()->to(AStacksScreen::Owed);
    }

    /** Where signing in again is. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    /**
     * Resume the session and send this title on it, the way asked.
     *
     * @param Closure(Stack, Session, AFoundTitle): HowAnAskWent $sending
     */
    private function sent(Closure $sending): WhatAskingShows
    {
        $title = $this->title();

        if (! $title instanceof AFoundTitle) {
            return WhatAskingShows::theSessionEnded();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAskingShows => $sending($stack, $session, $title)->either(
                answered: static fn(WhatAnAskCameTo $answer): WhatAskingShows => new HowAFoundTitleReads()->asked($answer),
                refused: function (Obstacle $why) use ($stack): WhatAskingShows {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAFoundTitleReads()->askMet($why);
                },
            ),
            notHeld: static fn(): WhatAskingShows => WhatAskingShows::theSessionEnded(),
        );
    }

    /** @param Closure(AFoundTitle): string $saying */
    private function said(Closure $saying): string
    {
        $title = $this->title();

        return $title instanceof AFoundTitle ? $saying($title) : '';
    }

    private function carried(WhatAFoundTitleIsOpenedWith $what): string
    {
        $carried = $this->data($what->value);

        return is_string($carried) ? $carried : '';
    }
}
