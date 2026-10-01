<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;
use Override;

/** The Blade tag for {@see Reorderable}: `<native:lemonfiber-reorderable>`. */
final class ReorderableTag extends NativeBladeComponent
{
    #[Override]
    protected bool $isSelfClosing = true;

    #[Override]
    protected function elementType(): string
    {
        return Reorderable::TYPE;
    }
}
