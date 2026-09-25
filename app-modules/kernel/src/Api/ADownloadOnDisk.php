<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One completed download on the machine, and what removing it would come to.
 *
 * A seeding download cannot be built without its ratio, and no other kind
 * can be built with one. What removing it costs, where the stack says it
 * costs anything, is built into the download rather than left for a
 * confirmation to say.
 */
final readonly class ADownloadOnDisk
{
    private function __construct(
        private string $name,
        private int $bytes,
        private WhereADownloadStands $stands,
        private ?ARatio $ratio,
        private string $consequence,
    ) {}

    /**
     * A download nothing ever took into a library.
     *
     * `$consequence` is what the stack says removing it costs, and empty
     * where it says nothing; so for each of the three.
     */
    public static function neverImported(string $name, int $bytes, string $consequence = ''): self
    {
        return self::made($name, $bytes, WhereADownloadStands::NeverImported, null, $consequence);
    }

    /** A download still being seeded, at this ratio. */
    public static function seeding(string $name, int $bytes, ARatio $ratio, string $consequence = ''): self
    {
        return self::made($name, $bytes, WhereADownloadStands::Seeding, $ratio, $consequence);
    }

    /** A download somebody asked to be left alone. */
    public static function leftAlone(string $name, int $bytes, string $consequence = ''): self
    {
        return self::made($name, $bytes, WhereADownloadStands::LeftAlone, null, $consequence);
    }

    /** What both the client and the services call it. */
    public function name(): string
    {
        return $this->name;
    }

    /** What it occupies, in bytes. */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /** Where it stands. */
    public function stands(): WhereADownloadStands
    {
        return $this->stands;
    }

    /**
     * Its ratio where it is seeding.
     *
     * @template TSeeding of object
     * @template TNot of object
     *
     * @param Closure(ARatio): TSeeding $seeding
     * @param Closure(): TNot           $notSeeding
     *
     * @return TSeeding|TNot
     */
    public function ratio(Closure $seeding, Closure $notSeeding): object
    {
        return $this->ratio instanceof ARatio ? $seeding($this->ratio) : $notSeeding();
    }

    /** What the stack says removing it costs, or empty where it said nothing. */
    public function consequence(): string
    {
        return $this->consequence;
    }

    /** One download, its name, size and cost refused where they are not one. */
    private static function made(string $name, int $bytes, WhereADownloadStands $stands, ?ARatio $ratio, string $consequence): self
    {
        if (trim($name) === '') {
            throw RoomSaysNothing::about('name');
        }

        if ($bytes < 0) {
            throw RoomSaysNothing::negative('bytes', $bytes);
        }

        // Empty is the stack saying nothing; blank is a sentence with nothing in it.
        if ($consequence !== '' && trim($consequence) === '') {
            throw RoomSaysNothing::about('consequence');
        }

        return new self($name, $bytes, $stands, $ratio, $consequence);
    }
}
