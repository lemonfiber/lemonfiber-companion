<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How full a machine is, where the room went, and what is on its disk to weigh.
 *
 * Read and never acted on. The stack decides where each volume stands and what
 * getting a line back would cost; nothing here decides anything about
 * removing.
 */
final readonly class WhereTheRoomWent
{
    private function __construct(
        private TheVolumes $volumes,
        private WhereTheRoomStands $stands,
        private TheAccount $account,
        private TheDownloadsOnDisk $downloads,
        private bool $halted,
    ) {}

    /** Everything one reading said. */
    public static function measured(
        TheVolumes $volumes,
        WhereTheRoomStands $stands,
        TheAccount $account,
        TheDownloadsOnDisk $downloads,
        bool $halted,
    ): self {
        return new self($volumes, $stands, $account, $downloads, $halted);
    }

    /** The volumes watched. */
    public function volumes(): TheVolumes
    {
        return $this->volumes;
    }

    /** Where the machine stands, which is where its worst volume stands. */
    public function stands(): WhereTheRoomStands
    {
        return $this->stands;
    }

    /** Whether the stack has stopped starting new downloads, to keep the services able to write. */
    public function isHalted(): bool
    {
        return $this->halted;
    }

    /** Where the room went, by category. */
    public function account(): TheAccount
    {
        return $this->account;
    }

    /** The completed downloads, each with where it stands. */
    public function downloads(): TheDownloadsOnDisk
    {
        return $this->downloads;
    }
}
