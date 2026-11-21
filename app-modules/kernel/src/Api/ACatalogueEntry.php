<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One plugin the stack's verified catalogue offers: the name it is installed by, its own name, what it does, its version, what the household is without it, and the version installed here where it is (ARCH-R451).
 *
 * Whether the catalogue's version is newer than the one installed is the
 * stack's answer, never this app's: a second comparer of versions here would
 * drift from the one that installs.
 */
final readonly class ACatalogueEntry
{
    private function __construct(
        private string $id,
        private string $name,
        private string $description,
        private string $version,
        private string $withoutIt,
        private string $installed,
        private bool $newer,
    ) {}

    /** An entry not installed here; a blank word is refused. */
    public static function offered(string $id, string $name, string $description, string $version, string $withoutIt): self
    {
        $said = ['id' => $id, 'name' => $name, 'description' => $description, 'version' => $version, 'without_it' => $withoutIt];

        foreach ($said as $field => $word) {
            if (trim($word) === '') {
                throw ThePluginCatalogueIsUnreadable::blank($field);
            }
        }

        return new self(trim($id), trim($name), trim($description), trim($version), trim($withoutIt), installed: '', newer: false);
    }

    /** The same entry, installed here at that version, and whether the stack says the catalogue's is newer; a blank version is refused. */
    public function installedAt(string $version, bool $newer): self
    {
        if (trim($version) === '') {
            throw ThePluginCatalogueIsUnreadable::blank('installed.version');
        }

        return new self($this->id, $this->name, $this->description, $this->version, $this->withoutIt, trim($version), $newer);
    }

    /** The name it is installed by. */
    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /** The version the catalogue offers. */
    public function version(): string
    {
        return $this->version;
    }

    /** What the household is without it (N5-R8). */
    public function withoutIt(): string
    {
        return $this->withoutIt;
    }

    /** The version installed here, or empty where it is not installed. */
    public function installed(): string
    {
        return $this->installed;
    }

    /** Whether the stack says the catalogue's version is newer than the one installed here. */
    public function isNewer(): bool
    {
        return $this->newer;
    }
}
