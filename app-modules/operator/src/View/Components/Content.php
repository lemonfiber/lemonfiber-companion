<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\View\HoldsItsSlot;
use Modules\Operator\Api\HowTheColumnScrolls;
use Override;

use function view;

/**
 * Where a screen's content sits: one padded column, holding the slot.
 *
 * It opens at its top, or at its end where what matters is last: a service's
 * lines arrive oldest first, and the one that says why it fell over is the
 * last, so that screen opens there and is scrolled back from.
 *
 * On a screen that asks the stack again, pulling it down asks again, so what
 * the house holds now is a pull away rather than a tab away. A column opened
 * from its end is not pulled down from its top, so {@see HowTheColumnScrolls}
 * is one of the three.
 *
 * Opened by the template `content` and closed by `content-closes`.
 * {@see HoldsItsSlot} says why a container that holds a slot is two
 * templates.
 */
final class Content extends Component
{
    use HoldsItsSlot;

    /** What pulling the screen down calls: the ask again every screen that asks the stack again has. */
    private const string ASKS_AGAIN = 'askAgain()';

    /** Where a column read from its end is anchored. */
    private const string AT_THE_END = 'bottom';

    public function __construct(
        public readonly HowTheColumnScrolls $scrolls = HowTheColumnScrolls::FromTheTop,
    ) {}

    public function render(): View
    {
        return view('operator::components.content-closes', ['pulled' => $this->pulledDownTo()]);
    }

    #[Override]
    protected function opens(): View
    {
        return view('operator::components.content', [
            'anchor' => $this->scrolls === HowTheColumnScrolls::FromTheEnd ? self::AT_THE_END : null,
            'pulled' => $this->pulledDownTo(),
        ]);
    }

    /** What pulling it down calls, or nothing where it is not pulled down. */
    private function pulledDownTo(): ?string
    {
        return $this->scrolls === HowTheColumnScrolls::PulledDownToAskAgain ? self::ASKS_AGAIN : null;
    }
}
