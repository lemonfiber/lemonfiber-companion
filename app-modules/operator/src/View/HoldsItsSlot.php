<?php

declare(strict_types=1);

namespace Modules\Operator\View;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A component whose slot is drawn inside the container it opens.
 *
 * Blade calls `shouldRender()` before it runs a component's slot, and renders
 * the component's own view after it. The native renderer draws each element
 * when it is evaluated. A template that wrapped `{{ $slot }}` in a container
 * would therefore draw the slot first and the container after it, empty.
 *
 * So the container is opened in `shouldRender()`, by the template `opens()`
 * answers with; the slot is drawn inside it; and the component's view,
 * rendered after the slot, closes it.
 *
 * @phpstan-require-extends Component
 */
trait HoldsItsSlot
{
    public function shouldRender(): bool
    {
        $this->opens()->render();

        return true;
    }

    /** The template that opens the container the slot is drawn in. */
    abstract protected function opens(): View;
}
