<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One service the stack dropped, flattened for a template.
 *
 * **`$replacedBy` is empty where nothing took its place, and the template must
 * branch on it rather than print it.** Nothing having replaced it is an answer,
 * and the commonest one; the value it comes from refuses a blank replacement,
 * so empty can only mean that.
 */
final readonly class AServiceDroppedAsShown
{
    /**
     * @param string $id         the id it was declared under, which is the name an operator looks for
     * @param string $removedIn  the stack version whose catalogue stopped carrying it
     * @param string $reason     why it went
     * @param string $replacedBy what took its place, or empty where nothing did
     */
    public function __construct(
        public string $id,
        public string $removedIn,
        public string $reason,
        public string $replacedBy,
    ) {}
}
