<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use function array_key_exists;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * The stack's plugin catalogue, as the block above the typed source and the notes on the installed rows draw it.
 *
 * Read and listed, switched off with the setting that turns it on, not
 * reached with the stack's problem, or not read yet. A catalogue that was not
 * read never draws as an empty one, and never as every plugin being up to date.
 */
final readonly class ThePluginCatalogueAsShown
{
    /**
     * @param bool                         $listed     whether the stack answered with the catalogue's entries
     * @param string                       $said       the catalogue key for why it is not listed, or empty
     * @param array<string, int|string>    $filling    what that key is filled with
     * @param string                       $remedy     the catalogue key for what to do about an obstacle, or empty
     * @param list<ACatalogueEntryAsShown> $entries    each entry, in the stack's order
     * @param AgoAsShown                   $published  how long ago the index was published
     * @param int                          $unreadable how many entries the index holds that could not be listed
     * @param string                       $reach      the setting that turns reaching it on, where it is switched off, or empty
     * @param array<string, string>        $newer      the catalogue's version, by the id of each installed plugin it is newer than
     */
    public function __construct(
        public bool $listed,
        public string $said,
        public array $filling,
        public string $remedy,
        public array $entries,
        public AgoAsShown $published,
        public int $unreadable,
        public string $reach,
        public array $newer,
    ) {}

    /** Whether it is still to be read, which draws nothing. */
    public function isBeingRead(): bool
    {
        return ! $this->listed && $this->said === '';
    }

    /** The catalogue's version where it is newer than the plugin installed under that id, or empty. */
    public function newerFor(string $id): string
    {
        return array_key_exists($id, $this->newer) ? $this->newer[$id] : '';
    }
}
