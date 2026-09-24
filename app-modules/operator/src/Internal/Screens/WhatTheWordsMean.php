<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheGlossary;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheWordsRead;
use Modules\Operator\Internal\ViewModels\TheWordsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function sprintf;
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
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhatTheWordsMean extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What somebody has typed into the search box.
     *
     * Public and handed to the view by name, for
     * {@see WhatThisServiceSaid::$looking}'s reason.
     */
    public string $looking = '';

    /** The glossary, once the frame has asked. */
    public ?TheGlossary $held = null;

    /** The word whose longer gloss is open, or empty. By name, so a search does not move it to another word. */
    public string $open = '';

    public function __construct(
        private readonly Explaining $explaining,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame, for
     * {@see WhatStoppedComingIn::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
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
                $this->open = $this->open === $word->word ? '' : $word->word;
            }
        }
    }

    /** Ask the machine again, forgetting the glossary it gave. */
    public function again(): void
    {
        $this->held = null;
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
            return new HowTheWordsRead()->this($held, LookingFor::text($this->looking), $this->open);
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
}
