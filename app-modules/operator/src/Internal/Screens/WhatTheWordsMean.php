<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheWordsRead;
use Modules\Operator\Internal\ViewModels\AWordAskedAbout;
use Modules\Operator\Internal\ViewModels\TheWordsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function sprintf;
use function trim;
use function view;

/**
 * What lemonfiber's words mean, as the stack's glossary explains them.
 *
 * Each word is drawn with its short gloss and what else it is called; the
 * longer gloss opens under it for whoever asks. The search is over the words
 * and their other names, since what somebody arriving from another tool knows
 * is what a thing is called there. Nothing here explains a word the glossary
 * does not carry.
 *
 * The glossary is asked for once and held, so typing narrows what is shown
 * without asking the machine again.
 *
 * **A word the held glossary has no entry for can be asked for alone.** The
 * operator asks, for the one word searched for, and only then: typing never
 * reaches the machine. A word the stack explains joins the glossary held here
 * and is drawn as every other word is; one it has no entry for either is shown
 * as it came, and not offered again.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatTheWordsMean extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What somebody has typed into the search box.
     *
     * Public and handed to the view by name, for
     * {@see WhatThisServiceSaid::$looking}'s reason.
     */
    public string $looking = '';

    /** The glossary, once the frame has asked. */
    public ?TheGlossary $held = null;

    /** The word whose longer gloss is open, by any name it goes by, or empty. By name, so a search does not move it to another word. */
    public string $open = '';

    /** What asking the stack for one word came to, where it did not explain it. */
    public ?AWordAskedAbout $asked = null;

    public function __construct(
        private readonly Explaining $explaining,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /**
     * Open on one word, where another screen sent somebody to it.
     *
     * The search is set to the word as well, so it is the one shown rather
     * than one of many somebody has to scroll to.
     */
    public function mount(string $service = ''): void
    {
        if (trim($service) === '') {
            return;
        }

        $this->looking = $service;
        $this->open = $service;
    }

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame, for
     * {@see WhatStoppedComingIn::stack()}'s reason.
     */
    public function stack(): Stack
    {
        return $this->around->stackOn($this);
    }

    /**
     * Open or close the longer gloss of the word at a place in the list as drawn.
     *
     * A place rather than the word, because a word is the stack's text and a
     * tap carries it through markup; the word is what is held.
     */
    public function toggle(string $place): void
    {
        foreach ($this->answer()->words as $index => $word) {
            if (sprintf('%d', $index) === $place) {
                $this->open = $word->isOpen ? '' : $word->word;
            }
        }
    }

    /**
     * Ask the machine for the one word searched for.
     *
     * Only where the held glossary has no entry for it and the stack has not
     * already said it has none either; anything else asks nothing. A session
     * this device no longer holds lets go of the glossary, so the next frame
     * says the session ended.
     */
    public function askTheStack(): void
    {
        $held = $this->held;
        $looking = LookingFor::text($this->looking);

        if (! $held instanceof TheGlossary || new HowTheWordsRead()->this($held, $looking, $this->open, $this->asked)->mayAsk === '') {
            return;
        }

        $stack = $this->stack();
        $word = AWordInUse::named($looking->typed());

        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheGlossary => $this->toldOf($stack, $session, $word, $held),
            notHeld: function (): TheGlossary {
                $this->held = null;

                return TheGlossary::of();
            },
        );
    }

    /** Ask the machine again, forgetting the glossary it gave and every word asked for. */
    public function again(): void
    {
        $this->held = null;
        $this->asked = null;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-the-words-mean', ['looking' => $this->looking]);
    }

    /** What came back, asked once and narrowed on every read. */
    public function answer(): TheWordsTurnedOutToBe
    {
        $held = $this->held;

        if ($held instanceof TheGlossary) {
            return new HowTheWordsRead()->this($held, LookingFor::text($this->looking), $this->open, $this->asked);
        }

        return $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheWordsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheWordsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheWordsTurnedOutToBe => new HowTheWordsRead()->signedOut(),
        );
    }

    /** What the machine said its words mean, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheWordsTurnedOutToBe
    {
        return $this->explaining->glossaryOn($stack, $session)->either(
            found: function (TheGlossary $words): TheWordsTurnedOutToBe {
                $this->held = $words;

                return new HowTheWordsRead()->this($words, LookingFor::text($this->looking), $this->open);
            },
            met: function (Obstacle $why) use ($stack): TheWordsTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheWordsRead()->met($why);
            },
        );
    }

    /**
     * What the machine said of one word, held as the glossary it now makes.
     *
     * A word it explained is added to the glossary held, which is how it is
     * drawn and found; anything else is held as what asking came to.
     */
    private function toldOf(Stack $stack, Session $session, AWordInUse $word, TheGlossary $held): TheGlossary
    {
        $this->asked = null;

        return $this->explaining->wordOn($stack, $session, $word)->either(
            explained: fn(AWord $entry): TheGlossary => $this->held = TheGlossary::of(...[...$held, $entry]),
            unexplained: function () use ($word, $held): TheGlossary {
                $this->asked = new AWordAskedAbout($word->said(), unexplained: true, met: '');

                return $held;
            },
            met: function (Obstacle $why) use ($stack, $word, $held): TheGlossary {
                $this->letGoOfTheSession($why, $stack);
                $this->asked = new AWordAskedAbout($word->said(), unexplained: false, met: $why->said());

                return $held;
            },
        );
    }
}
