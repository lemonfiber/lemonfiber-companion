<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARoomGivenBackAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\GivingBackRoom;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Measuring;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\Presenters\HowGivingRoomBackReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\HowGivingItBackWent;
use Modules\Operator\Internal\ViewModels\WhatGivingItBackWouldShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Giving back one category of the room the stack offers as costing nothing, confirmed once and carried out only on a yes.
 *
 * **The offer is read fresh.** The screen reads the machine's room again and
 * finds its category in the offer, so what the operator confirms is the
 * category, its size and how many files it holds as the stack says them now,
 * and that giving it back cannot be undone. A category the offer no longer
 * holds offers nothing to agree to.
 *
 * **Only that category of that offer is given back.** The yes quotes the
 * offer by the name the stack gave it and names the one category, so nothing
 * else on the machine is touched. Where the offer moved in between, the stack
 * removes nothing; what it said is kept and the offer is read again for a
 * fresh yes.
 *
 * **What came back is the stack's report alone:** the room returned, how many
 * items went, and the stack's reason for each item left behind. Where an item
 * is on the machine is never drawn. Storage reads the room afresh when it is
 * opened again.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class GivingRoomBack extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::giving-room-back';

    /**
     * What the offer came to, once the frame has read it.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public ?WhatGivingItBackWouldShow $answered = null;

    /**
     * The offer, while it holds the category to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * sent against the offer itself.
     */
    public ?TheRoomOnOffer $offered = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    /** The handle agreeing answered. Not shown and never kept past this screen. */
    public ?string $handle = null;

    /** What became of the yes, once this frame has asked. */
    public ?HowGivingItBackWent $carriedOut = null;

    /** What the stack said where the offer a yes was given for had moved, until the next yes. */
    public ?ARefusalAsShown $movedOn = null;

    public function __construct(
        private readonly Measuring $measuring,
        private readonly GivingBackRoom $givingBack,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->isWorking();
    }

    /** The category this screen is about, as the route carries it. */
    public function categoryNamed(): string
    {
        return $this->around->aboutOn($this);
    }

    /** What giving the category back would come to, read once per frame. */
    public function answer(): WhatGivingItBackWouldShow
    {
        return $this->answered ??= $this->read();
    }

    /** Whether the operator has agreed to give it back. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowGivingItBackWent
    {
        return $this->carriedOut ??= $this->followed();
    }

    /**
     * Give the category back as the offer on the screen holds it.
     *
     * Silent where no offer holding it is held, which is a frame that has not
     * read one or an offer without it: there is nothing to agree to.
     */
    public function agree(): void
    {
        $offer = $this->offered;
        $category = WhatALineIsAbout::tryFrom($this->categoryNamed());

        if (! $offer instanceof TheRoomOnOffer || ! $category instanceof WhatALineIsAbout || ! $offer->holds($category)) {
            return;
        }

        $stack = $this->stack();
        $agreed = ARoomGivenBackAgreed::to($offer, $category);
        $this->agreed = true;
        $this->handle = null;
        $this->movedOn = null;

        $this->carriedOut = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowGivingItBackWent => $this->shown($this->givingBack->giveBack($stack, $session, $agreed), $stack),
            notHeld: static fn(): HowGivingItBackWent => new HowGivingRoomBackReads()->signedOut(),
        );
    }

    /**
     * Ask again, because the operator said so.
     *
     * After a yes the stack took on, it asks after the same handle. Otherwise
     * it reads the offer afresh, because an offer can move on and a refused
     * yes gave nothing back.
     */
    public function again(): void
    {
        $this->carriedOut = null;

        if ($this->agreed && is_string($this->handle)) {
            return;
        }

        $this->agreed = false;
        $this->offered = null;
        $this->answered = null;
    }

    /**
     * Read again while the stack is giving the room back.
     *
     * It does nothing unless that is running, and it only ever reads: the yes
     * is sent by {@see agree()} and nothing else. The interval is
     * {@see HowOftenAScreenLooks}'s constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->isWorking()) {
            $this->carriedOut = null;
        }
    }

    /** Whether the stack is giving the room back right now. */
    public function isWorking(): bool
    {
        return $this->agreed && $this->done()->stands->isWorking();
    }

    /** Resume the session and read the machine's room, for the category's place in the offer. */
    private function read(): WhatGivingItBackWouldShow
    {
        $category = WhatALineIsAbout::tryFrom($this->categoryNamed());

        if (! $category instanceof WhatALineIsAbout) {
            return new HowGivingRoomBackReads()->namesNoCategory();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatGivingItBackWouldShow => $this->measuring->measuredOn($stack, $session)->either(
                measured: fn(WhereTheRoomWent $room): WhatGivingItBackWouldShow => $this->offerOf($room->offer(), $category),
                met: $this->lettingGoIfRefused($stack, new HowGivingRoomBackReads()->notOffered(...)),
            ),
            notHeld: static fn(): WhatGivingItBackWouldShow => new HowGivingRoomBackReads()->notAsked(),
        );
    }

    /** The category as the offer holds it, the offer held to agree against; an offer without it offers nothing. */
    private function offerOf(TheRoomOnOffer $offer, WhatALineIsAbout $about): WhatGivingItBackWouldShow
    {
        foreach ($offer as $category) {
            if ($category->about() === $about) {
                $this->offered = $offer;

                return new HowGivingRoomBackReads()->offering($category);
            }
        }

        return new HowGivingRoomBackReads()->notOnOffer();
    }

    /** Ask the stack what became of the yes. */
    private function followed(): HowGivingItBackWent
    {
        $held = $this->handle;

        if (! is_string($held)) {
            return new HowGivingRoomBackReads()->ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowGivingItBackWent => $this->shown($this->givingBack->whatBecameOf($stack, $session, Job::named($held)), $stack),
            notHeld: static fn(): HowGivingItBackWent => new HowGivingRoomBackReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow.
     *
     * A yes refused because the offer moved is no failure: nothing was given
     * back, what the stack said is kept, and the offer is read again.
     *
     * @param WhatBecameOfTheWork<TheRoomGivenBack> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): HowGivingItBackWent
    {
        return $became->eitherOrMoved(
            underway: function (Job $job): HowGivingItBackWent {
                $this->handle = $job->shown();

                return new HowGivingRoomBackReads()->running();
            },
            answered: static fn(TheRoomGivenBack $report): HowGivingItBackWent => new HowGivingRoomBackReads()->done($report),
            ended: static fn(): HowGivingItBackWent => new HowGivingRoomBackReads()->ended(),
            refused: static fn(ARefusalInItsWords $why): HowGivingItBackWent => new HowGivingRoomBackReads()->refused($why),
            met: $this->lettingGoIfRefused($stack, static fn(Obstacle $why): HowGivingItBackWent => new HowGivingRoomBackReads()->met($why)),
            moved: function (ARefusalInItsWords $why): HowGivingItBackWent {
                $this->movedOn = new HowARefusalReads()->inItsWords($why);
                $this->agreed = false;
                $this->handle = null;
                $this->offered = null;
                $this->answered = null;

                return new HowGivingRoomBackReads()->ended();
            },
        );
    }
}
