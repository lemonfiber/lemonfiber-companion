<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How full a machine is, where the room went, and what is on its disk to weigh.
 *
 * The stack decides where each volume stands, what getting a line back would
 * cost and which categories it offers back as costing nothing.
 */
final readonly class WhereTheRoomWent
{
    private function __construct(
        private TheVolumes $volumes,
        private WhereTheRoomStands $stands,
        private TheAccount $account,
        private TheDownloadsOnDisk $downloads,
        private TheRoomOnOffer $offer,
        private bool $halted,
    ) {}

    /** Everything one reading said. */
    public static function measured(
        TheVolumes $volumes,
        WhereTheRoomStands $stands,
        TheAccount $account,
        TheDownloadsOnDisk $downloads,
        TheRoomOnOffer $offer,
        bool $halted,
    ): self {
        return new self($volumes, $stands, $account, $downloads, $offer, $halted);
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

    /** The room offered back as costing nothing, by category. */
    public function offer(): TheRoomOnOffer
    {
        return $this->offer;
    }

    /** The completed downloads, each with where it stands. */
    public function downloads(): TheDownloadsOnDisk
    {
        return $this->downloads;
    }
}
