<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One plugin, as a row and as the head of an install's account: what it is, and what vouches for it.
 *
 * Reviewed or not is on every one, because a plugin nobody reviewed is to be
 * said to be one wherever it appears.
 */
final readonly class APluginAsShown
{
    /**
     * @param string               $name         what it calls itself, or its id
     * @param string               $id           the name it is installed under
     * @param string               $version      its own version
     * @param bool                 $reviewed     whether anybody reviewed it before it was installed
     * @param string               $source       where it came from, or empty
     * @param string               $revision     the commit it came at, or empty
     * @param string               $signed       the key that signed it, or empty
     * @param string               $upstream     where its own source is published, or empty
     * @param string               $licence      its licence, or empty
     * @param string               $standingSaid how its source stands, as a catalogue key, or empty where the stack did not say
     * @param string               $standingWhy  the stack's reason for how it stands, or empty
     * @param list<ARecipeAsShown> $recipes      its recipes, each in full
     * @param bool                 $updatable    whether its record names a source an update can fetch again
     */
    public function __construct(
        public string $name,
        public string $id,
        public string $version,
        public bool $reviewed,
        public string $source,
        public string $revision,
        public string $signed,
        public string $upstream,
        public string $licence,
        public string $standingSaid,
        public string $standingWhy,
        public array $recipes,
        public bool $updatable,
    ) {}
}
