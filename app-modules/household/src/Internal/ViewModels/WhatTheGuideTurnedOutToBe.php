<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use function array_key_exists;

/**
 * The live TV guide as it is drawn: the half hours along the top and a row for each channel, or why it could not be had.
 *
 * Also what came of the last channel pressed here, the later programme whose
 * sheet is open, and the programmes drawn together whose list is open. One of
 * the two is open at most: a later programme pressed in the list opens its
 * sheet in the list's place.
 */
final readonly class WhatTheGuideTurnedOutToBe
{
    /**
     * @param list<WhatAMarkSays>     $marks
     * @param list<WhatAGuideRowSays> $rows
     */
    private function __construct(
        public array $marks,
        public array $rows,
        public bool $cameBack,
        public ?WhatStoppedAMember $stopped,
        public ?WhatAChannelCameTo $came,
        public ?WhatAProgrammeSays $about,
        public ?TheProgrammesTogether $listing,
    ) {}

    /**
     * @param list<WhatAMarkSays>     $marks
     * @param list<WhatAGuideRowSays> $rows
     */
    public static function these(array $marks, array $rows): self
    {
        return new self($marks, $rows, cameBack: true, stopped: null, came: null, about: null, listing: null);
    }

    public static function met(WhatStoppedAMember $stopped): self
    {
        return new self([], [], cameBack: false, stopped: $stopped, came: null, about: null, listing: null);
    }

    public static function signedOut(): self
    {
        return new self([], [], cameBack: false, stopped: null, came: null, about: null, listing: null);
    }

    /** The same guide, saying what came of pressing one of its channels. */
    public function after(WhatAChannelCameTo $came): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $came->isNothing() ? null : $came, $this->about, $this->listing);
    }

    /** The same guide with no sheet and no list open. */
    public function closed(): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $this->came, null, null);
    }

    /** The same guide with the sheet of the later programme at that place open, or none where nothing later sits there. */
    public function about(int $row, int $at): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $this->came, $this->sheetFor($row, $at), null);
    }

    /** The same guide with the list of the programmes drawn together in that cell of that row open, or none where the cell holds no several. */
    public function listing(int $row, int $place): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $this->came, null, $this->listFor($row, $place));
    }

    /** The name a channel is drawn with, or its id where the guide does not hold it. */
    public function nameOf(string $id): string
    {
        foreach ($this->rows as $row) {
            if ($row->id === $id) {
                return $row->name;
            }
        }

        return $id;
    }

    public function isSignedOut(): bool
    {
        return ! $this->cameBack && ! $this->stopped instanceof WhatStoppedAMember;
    }

    private function sheetFor(int $row, int $at): ?WhatAProgrammeSays
    {
        foreach ($this->programmesIn($row) as $programme) {
            if ($programme->at === $at && ! $programme->isOnNow && ! $programme->isAGap()) {
                return new WhatAProgrammeSays($this->rows[$row]->name, $programme->title, $programme->times, $programme->certificate);
            }
        }

        return null;
    }

    private function listFor(int $row, int $place): ?TheProgrammesTogether
    {
        if (! array_key_exists($row, $this->rows) || ! array_key_exists($place, $this->rows[$row]->cells)) {
            return null;
        }

        $cell = $this->rows[$row]->cells[$place];

        return $cell->isSeveral() ? new TheProgrammesTogether($this->rows[$row]->name, $cell->times, $cell->together) : null;
    }

    /**
     * Every programme in a row, those drawn together each on its own, in the order they run.
     *
     * @return list<WhatACellSays>
     */
    private function programmesIn(int $row): array
    {
        $programmes = [];

        foreach ($this->rows[$row]->cells ?? [] as $cell) {
            $programmes = [...$programmes, ...($cell->isSeveral() ? $cell->together : [$cell])];
        }

        return $programmes;
    }
}
