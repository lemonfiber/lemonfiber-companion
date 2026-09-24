<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ALineOfTheAccount;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ADownloadAsShown;
use Modules\Operator\Internal\ViewModels\ALineAsShown;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\AVolumeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheRoomTurnedOutToBe;

/**
 * What asking a stack how full it is produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out, with the moment a network share's age is
 * counted from handed in (`B1`). Every size goes through {@see HowBig}. A
 * figure the stack could not read stays absent rather than becoming nought.
 */
final readonly class HowTheRoomReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheRoomTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is how full it is. */
    public function this(WhereTheRoomWent $room, Instant $now): TheRoomTurnedOutToBe
    {
        $volumes = [];

        foreach ($room->volumes() as $volume) {
            $volumes[] = $this->volume($volume, $now);
        }

        $account = [];

        foreach ($room->account() as $line) {
            $account[] = $this->line($line);
        }

        $downloads = [];

        foreach ($room->downloads() as $download) {
            $downloads[] = $this->download($download);
        }

        return new TheRoomTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            standsSaid: $room->stands()->saidOnTheScreen(),
            halted: $room->isHalted(),
            volumes: $volumes,
            account: $account,
            downloads: $downloads,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheRoomTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheRoomTurnedOutToBe
    {
        return new TheRoomTurnedOutToBe(went: $went, standsSaid: '', halted: false, volumes: [], account: [], downloads: []);
    }

    /** One volume, with how old its reading is against the frame's moment. */
    private function volume(AVolume $volume, Instant $now): AVolumeAsShown
    {
        $ago = $volume->reading()->either(
            live: static fn(): AsText => AsText::nothing(),
            asOf: static fn(Instant $taken): AgoAsShown => AgoAsShown::from(HowLongAgo::since($taken, $now), $taken, $now),
        );

        return new AVolumeAsShown(
            holdsSaid: $volume->holds()->saidOnTheScreen(),
            point: $volume->point(),
            standsSaid: $volume->stands()->saidOnTheScreen(),
            free: $this->amount($volume->free()),
            limit: $this->amount($volume->limit()),
            committed: $this->size($volume->committed()),
            projected: $this->amount($volume->projected()),
            agoSaid: $ago instanceof AgoAsShown ? $ago->said : '',
            agoCount: $ago instanceof AgoAsShown ? $ago->count : 0,
        );
    }

    /** One line of the account, with its unshared figure only where it differs. */
    private function line(ALineOfTheAccount $line): ALineAsShown
    {
        $occupies = $line->occupies();

        return new ALineAsShown(
            aboutSaid: $line->about()->saidOnTheScreen(),
            tree: $line->tree(),
            occupies: $this->size($occupies->physical()),
            unshared: $occupies->differs() ? $this->size($occupies->logical()) : null,
            costsSaid: $line->costs()->saidOnTheScreen(),
        );
    }

    /** One completed download, with its ratio where it is seeding. */
    private function download(ADownloadOnDisk $download): ADownloadAsShown
    {
        $ratio = $download->ratio(
            seeding: static fn(ARatio $ratio): RatioAsShown => $ratio->either(
                read: static fn(string $read): RatioAsShown => new RatioAsShown('stacks.room.ratio', $read),
                none: static fn(): RatioAsShown => new RatioAsShown('stacks.room.no_ratio', ''),
            ),
            notSeeding: static fn(): RatioAsShown => new RatioAsShown('', ''),
        );

        return new ADownloadAsShown(
            name: $download->name(),
            size: $this->size($download->bytes()),
            standingSaid: $download->stands()->saidOnTheScreen(),
            ratioSaid: $ratio->said,
            ratio: $ratio->ratio,
            consequence: $download->consequence(),
        );
    }

    /** A figure a reading may not have, as a size or as nothing. */
    private function amount(AnAmountOfRoom $amount): ?ASizeAsShown
    {
        $shown = $amount->either(
            known: fn(int $bytes): ASizeAsShown => $this->size($bytes),
            unread: static fn(): AsText => AsText::nothing(),
        );

        return $shown instanceof ASizeAsShown ? $shown : null;
    }

    /** Bytes, as a figure and a unit. */
    private function size(int $bytes): ASizeAsShown
    {
        $big = HowBig::of($bytes);

        return new ASizeAsShown(figure: $big->figure, unit: $big->said);
    }
}
