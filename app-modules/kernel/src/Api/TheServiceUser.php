<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The user and group a stack's services run as, and where the two come from.
 */
final readonly class TheServiceUser
{
    private function __construct(
        private int $uid,
        private int $gid,
        private WhereTheServiceUserComesFrom $from,
    ) {}

    /** The services run as this user and group, which come from there. */
    public static function runsAs(int $uid, int $gid, WhereTheServiceUserComesFrom $from): self
    {
        return new self($uid, $gid, $from);
    }

    /** The user and group as the command line writes them, `uid:gid`. */
    public function asTyped(): string
    {
        return sprintf('%d:%d', $this->uid, $this->gid);
    }

    /** Where the two come from. */
    public function from(): WhereTheServiceUserComesFrom
    {
        return $this->from;
    }
}
