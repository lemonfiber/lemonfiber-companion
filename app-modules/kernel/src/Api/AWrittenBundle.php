<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function in_array;
use function mb_strrpos;
use function mb_substr;

/**
 * A support bundle the stack wrote, and the name it is fetched by.
 *
 * The stack answers a written bundle with the path it wrote the file to, and
 * serves that file by the path's last segment. So the name is read off the
 * path rather than carried beside it, and a path whose last segment names no
 * file is refused where the path arrives: a bundle fetched by nothing, or by a
 * step up the tree, would be a request for something other than that file.
 */
final readonly class AWrittenBundle
{
    /** The last segments that name no file. */
    private const array NO_FILE = ['', '.', '..'];

    private function __construct(private string $path, private string $name) {}

    /** The bundle written to that path; a path ending in no file's name is refused. */
    public static function at(string $path): self
    {
        $slash = mb_strrpos($path, '/');
        $name = $slash === false ? $path : mb_substr($path, $slash + 1);

        if (in_array($name, self::NO_FILE, strict: true)) {
            throw ABundleHasNoName::whereItWasWritten();
        }

        return new self($path, $name);
    }

    /** Where on the stack's machine it was written. */
    public function path(): string
    {
        return $this->path;
    }

    /** What the file is called, which is what it is fetched and handed over by. */
    public function name(): string
    {
        return $this->name;
    }
}
