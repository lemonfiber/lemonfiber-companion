<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\View\Tone;

use function view;

/**
 * Where something stands, as a glyph and words: the glyph says the tone, and
 * the words say the rest, so the state is never in colour alone.
 *
 * A standing given a word in one says it twice: as what a screen reader hears
 * for the glyph, and in small type under the words, so a heading that names a
 * thing rather than a state still says the state.
 */
final class Standing extends Component
{
    public readonly Tone $says;

    public readonly string $ink;

    public readonly string $paper;

    public function __construct(
        public readonly string $said,
        string $tone,
        public readonly string $note = '',
        public readonly string $word = '',
    ) {
        $this->says = Tone::from($tone);
        $this->ink = ThemeToken::Text->light();
        $this->paper = ThemeToken::Text->dark();
    }

    public function render(): View
    {
        return view('design::components.standing');
    }
}
