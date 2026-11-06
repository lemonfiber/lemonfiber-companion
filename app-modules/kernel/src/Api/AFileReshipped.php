<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One starting file whose placeholders an update changes: as the installed version shipped it and as the new one ships it. */
final readonly class AFileReshipped
{
    private function __construct(private string $path, private ThePlaceholders $was, private ThePlaceholders $becomes) {}

    /** The file; a blank path is refused. */
    public static function at(string $path, ThePlaceholders $was, ThePlaceholders $becomes): self
    {
        if (trim($path) === '') {
            throw PluginSaysNothing::about('path');
        }

        return new self($path, $was, $becomes);
    }

    /** Where the file is. */
    public function path(): string
    {
        return $this->path;
    }

    /** Its placeholders as the installed version shipped it. */
    public function was(): ThePlaceholders
    {
        return $this->was;
    }

    /** Its placeholders as the new version ships it. */
    public function becomes(): ThePlaceholders
    {
        return $this->becomes;
    }
}
