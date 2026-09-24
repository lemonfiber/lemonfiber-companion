<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The bar a screen opens with.
 *
 * **No slot.** A native element is collected the moment it is evaluated, and
 * Blade evaluates slot content before the component's own template runs — so a
 * chrome whose template wrapped its screen's slot collected the screen's
 * elements first, as siblings of the implicit root, and its own padded column
 * afterwards and empty. Nothing threw; the padding simply had nothing inside
 * it. The package names the same shape as an error for its own child
 * components: pass data via props.
 *
 * So the chrome is pieces a screen emits in order, not a box it sits in. The
 * screen's content does sit in a box, {@see Content}, and
 * {@see \Modules\Operator\View\HoldsItsSlot} says how a box draws its slot
 * inside it.
 */
final class ScreenOpens extends Component
{
    public function __construct(public readonly string $title) {}

    public function render(): View
    {
        return view('operator::components.screen-opens');
    }
}
