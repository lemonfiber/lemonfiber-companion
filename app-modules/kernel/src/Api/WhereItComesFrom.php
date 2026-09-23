<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where one service comes from, as the stack declares it.
 *
 * An image, the version this stack pins it at, the project it is built from
 * and the licence it is published under. All four are what somebody is
 * entitled to know about software running on their own machine, and none is
 * optional: a licence shown only where it is unusual is a licence nobody can
 * tell was checked.
 *
 * **Every word is the stack's, and none is ever looked up.** The upstream is
 * where somebody goes to check the licence against the project, not something
 * this app asks — so a project that cannot be reached leaves every field here
 * exactly as true as it was. The pin and the licence are facts the stack wrote
 * down, and they stay facts.
 *
 * **The version is kept apart from the image**, as the stack keeps it, so a
 * caller comparing what is pinned against what a project released compares
 * versions rather than parsing them out of a reference.
 */
final readonly class WhereItComesFrom
{
    private function __construct(
        private ServiceId $service,
        private string $name,
        private string $image,
        private string $pinned,
        private string $upstream,
        private string $licence,
    ) {}

    /** One service's origin, every word of it required. */
    public static function declared(
        ServiceId $service,
        string $name,
        string $image,
        string $pinned,
        string $upstream,
        string $licence,
    ): self {
        return new self(
            $service,
            self::said('name', $name),
            self::said('image', $image),
            self::said('pinned', $pinned),
            self::said('upstream', $upstream),
            self::said('licence', $licence),
        );
    }

    /** Which service this is, as the stack names it. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** What it is called in front of an operator. */
    public function name(): string
    {
        return $this->name;
    }

    /** The image it runs, without a version. */
    public function image(): string
    {
        return $this->image;
    }

    /** The exact version this stack pins it at. */
    public function pinned(): string
    {
        return $this->pinned;
    }

    /** The project it is built from, where somebody checks the licence. */
    public function upstream(): string
    {
        return $this->upstream;
    }

    /** The licence it is published under, as the identifier the stack wrote. */
    public function licence(): string
    {
        return $this->licence;
    }

    /** A word, refused where it is blank. */
    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw OriginSaysNothing::about($field);
        }

        return $word;
    }
}
