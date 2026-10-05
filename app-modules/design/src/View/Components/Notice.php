<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\View\HoldsItsSlot;
use Modules\Design\View\Tone;
use Override;

use function view;

/**
 * Something the reader is told before anything else on the screen: what
 * stopped a reading, what a stack refused. Raised off the ground on its tone's
 * ground, with its tone's glyph beside the lines it holds.
 *
 * Opened by `notice` and closed by `notice-closes` ({@see HoldsItsSlot}).
 */
final class Notice extends Component
{
    use HoldsItsSlot;

    public readonly Tone $says;

    public function __construct(private readonly WhichThemeIsOnTheGlass $glass, string $tone = 'attention')
    {
        $this->says = Tone::from($tone);
    }

    public function render(): View
    {
        return view('design::components.notice-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('design::components.notice', [
            'says' => $this->says,
            'colour' => $this->says->colour()->in($this->glass->whose()),
            'warns' => $this->says->ground() === ThemeToken::WarnTint,
            'alarms' => $this->says->ground() === ThemeToken::AlarmTint,
        ]);
    }
}
