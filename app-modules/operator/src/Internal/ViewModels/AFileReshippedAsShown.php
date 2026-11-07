<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One starting file whose placeholders an update changes, as the installed version shipped it and as the new one ships it. */
final readonly class AFileReshippedAsShown
{
    /**
     * @param list<APlaceholderAsShown> $was
     * @param list<APlaceholderAsShown> $becomes
     */
    public function __construct(public string $path, public array $was, public array $becomes) {}
}
