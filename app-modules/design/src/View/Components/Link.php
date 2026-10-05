<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;

use function view;

/**
 * Words that are tapped, with a chevron saying so: the quiet way to a place
 * or an act, beside a screen's one filled button.
 *
 * The target is 48 tall whatever the words are. `answersTo` names what it
 * acts on where the words alone would not, as in a list of findings that each
 * offer the same words.
 */
final class Link extends Component
{
    public readonly string $named;

    public readonly string $colour;

    /**
     * @param array<string, string> $carries what the road hands the screen it opens, besides the route
     */
    public function __construct(
        WhichThemeIsOnTheGlass $glass,
        public readonly string $label,
        public readonly string $tap = '',
        public readonly string $goes = '',
        string $answersTo = '',
        public readonly array $carries = [],
    ) {
        $this->named = $answersTo === '' ? $label : $answersTo;
        $this->colour = ThemeToken::Muted->in($glass->whose());
    }

    public function render(): View
    {
        return view('design::components.link');
    }
}
