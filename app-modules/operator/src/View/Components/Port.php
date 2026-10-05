<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\View\Tone;

use function view;

/**
 * The tile a service ends in, saying how badly it wants the operator.
 *
 * A port the operator can pass over is raised on a hairline with its tone's
 * glyph in its tone's colour. One that wants looking at sits on the warning
 * tint inside a warning edge, and one that is broken is filled with the alarm
 * colour and its glyph drawn in ink on it, so the only tiles with weight on a
 * screen are the ones that want the operator. The glyph is the tone's own
 * shape, so the tile says its state without its colour. `label` is read aloud
 * where no words beside the tile say the same thing, and left out where they
 * do, which makes the tile furniture.
 */
final class Port extends Component
{
    public readonly Tone $says;

    public readonly string $colour;

    public readonly bool $warns;

    public readonly bool $alarms;

    public function __construct(WhichThemeIsOnTheGlass $glass, string $tone, public readonly string $label = '')
    {
        $this->says = Tone::from($tone);
        $this->warns = $this->says->ground() === ThemeToken::WarnTint;
        $this->alarms = $this->says->ground() === ThemeToken::AlarmTint;
        $this->colour = ($this->alarms ? ThemeToken::OnAlarm : $this->says->colour())->in($glass->whose());
    }

    public function render(): View
    {
        return view('operator::components.port');
    }
}
