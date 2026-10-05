<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\View\Tone;

use function view;

/**
 * A line of machine text with a tone's glyph in front of it.
 *
 * What a service wrote, drawn as {@see Verbatim} draws it, beside the glyph of
 * the tone it earned. The glyph is named for a screen reader by the word it
 * stands for, because a reader has no other way to hear that this line is the
 * error among two hundred.
 *
 * The line is an attribute rather than a slot, so there is no branch here that
 * could drop one.
 */
final class MarkedLine extends Component
{
    public readonly Tone $says;

    public readonly string $colour;

    public function __construct(
        WhichThemeIsOnTheGlass $glass,
        public readonly string $line,
        string $tone,
        public readonly string $word,
    ) {
        $this->says = Tone::from($tone);
        $this->colour = ThemeToken::Text->in($glass->whose());
    }

    public function render(): View
    {
        return view('design::components.marked-line');
    }
}
