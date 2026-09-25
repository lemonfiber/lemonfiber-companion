<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheGlossary;
use Modules\Operator\Internal\Presenters\HowAGlossReads;
use Modules\Operator\Internal\ViewModels\AGlossAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that explains lemonfiber's words where it draws them.
 *
 * The glossary is asked for the first time a word is drawn and held after,
 * so a screen that draws no word asks for nothing and one that draws many
 * asks once. A glossary that could not be had explains nothing, and the
 * screen's words are then drawn as they came; the screen's own reading says
 * what stood in the way.
 *
 * **It reads the using screen's own `$storage`**, for the reason
 * {@see LetsGoOfARefusedSession} gives, and lets go of a session the stack
 * refused through that trait, which the screen also uses.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ShowsWhatItsWordsMean
{
    /** The glossary, once a word has been drawn. Public for {@see Screens\WhatThisServiceSaid::$looking}'s reason. */
    public ?TheGlossary $glossary = null;

    /** What a word this screen draws means, or nothing where the glossary does not carry it. */
    public function gloss(string $word): AGlossAsShown
    {
        $this->glossary ??= $this->glossaryOf($this->stack());

        return new HowAGlossReads()->of($this->glossary, AWordInUse::named($word), $this->goes());
    }

    /** The stack whose glossary explains this screen's words. */
    abstract public function stack(): Stack;

    /** Where that stack's screens are. */
    abstract public function goes(): WhereAStackIs;

    /** Where the glossary comes from. */
    abstract protected function explaining(): Explaining;

    /** The stack's glossary, or one with no words where it could not be had. */
    private function glossaryOf(Stack $stack): TheGlossary
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheGlossary => $this->explaining()->glossaryOn($stack, $session)->either(
                found: static fn(TheGlossary $words): TheGlossary => $words,
                met: function (Obstacle $why) use ($stack): TheGlossary {
                    $this->letGoOfTheSession($why, $stack);

                    return TheGlossary::of();
                },
            ),
            notHeld: static fn(): TheGlossary => TheGlossary::of(),
        );
    }
}
