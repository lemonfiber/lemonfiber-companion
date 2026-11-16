<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * The live TV guide as it is drawn: the half hours along the top and a row for each channel, or why it could not be had.
 *
 * Also what came of the last channel pressed here, and the later programme
 * whose sheet is open.
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
    ) {}

    /**
     * @param list<WhatAMarkSays>     $marks
     * @param list<WhatAGuideRowSays> $rows
     */
    public static function these(array $marks, array $rows): self
    {
        return new self($marks, $rows, cameBack: true, stopped: null, came: null, about: null);
    }

    public static function met(WhatStoppedAMember $stopped): self
    {
        return new self([], [], cameBack: false, stopped: $stopped, came: null, about: null);
    }

    public static function signedOut(): self
    {
        return new self([], [], cameBack: false, stopped: null, came: null, about: null);
    }

    /** The same guide, saying what came of pressing one of its channels. */
    public function after(WhatAChannelCameTo $came): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $came->isNothing() ? null : $came, $this->about);
    }

    /** The same guide with no sheet open. */
    public function closed(): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $this->came, null);
    }

    /** The same guide with the sheet of the later programme at that place open, or none where nothing later sits there. */
    public function about(int $row, int $at): self
    {
        return new self($this->marks, $this->rows, $this->cameBack, $this->stopped, $this->came, $this->sheetFor($row, $at));
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
        foreach ($this->rows[$row]->cells ?? [] as $cell) {
            if ($cell->at === $at && ! $cell->isOnNow && ! $cell->isAGap()) {
                return new WhatAProgrammeSays($this->rows[$row]->name, $cell->title, $cell->times, $cell->certificate);
            }
        }

        return null;
    }
}
