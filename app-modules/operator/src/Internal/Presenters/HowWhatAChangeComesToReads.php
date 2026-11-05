<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_push;

use Modules\Kernel\Api\AnEditFoundInTheFile;
use Modules\Kernel\Api\WhatAChangeAsksFor;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ALineOfWhatAChangeComesTo;
use Modules\Operator\Internal\ViewModels\WhatAChangeComesToAsShown;

/**
 * What a proposed change comes to, turned into the lines the screen draws before the yes.
 */
final readonly class HowWhatAChangeComesToReads
{
    public function in(WhatAChangeComesTo $comesTo): WhatAChangeComesToAsShown
    {
        $lines = [];
        $means = $comesTo->whatItMeans(said: AsText::of(...), nothing: AsText::nothing(...))->said;

        if ($means !== '') {
            $lines[] = $this->words($means, aside: false);
        }

        foreach ($comesTo->stops as $service) {
            $lines[] = $this->line('config.findings.stops', ['service' => $service], aside: false);
        }

        foreach ($comesTo->interrupts as $download) {
            $lines[] = $this->line($download->by->saidOnTheScreen(), ['name' => $download->name, 'progress' => $download->progress], aside: false);
        }

        foreach ($comesTo->libraries as $library) {
            $lines[] = $this->line($library->carried ? 'config.findings.library_carried' : 'config.findings.library_not_carried', ['service' => $library->service, 'path' => $library->path], aside: false);
            $lines[] = $this->words($library->because, aside: true);
        }

        foreach ($comesTo->asks as $ask) {
            array_push($lines, ...$this->asked($ask));
        }

        $edited = $comesTo->theEdit(
            found: fn(AnEditFoundInTheFile $edit): WhatAChangeComesToAsShown => new WhatAChangeComesToAsShown([
                $this->line('config.findings.edited', [], aside: false),
                $this->side($edit->wrote, 'config.findings.wrote', 'config.findings.wrote_empty', 'config.findings.wrote_withheld'),
                $this->side($edit->found, 'config.findings.found', 'config.findings.found_empty', 'config.findings.found_withheld'),
            ]),
            none: static fn(): WhatAChangeComesToAsShown => new WhatAChangeComesToAsShown([]),
        );
        array_push($lines, ...$edited->lines);

        foreach ($comesTo->keeps as $service) {
            $lines[] = $this->line('config.findings.keeps', ['service' => $service], aside: true);
        }

        return new WhatAChangeComesToAsShown($lines);
    }

    /** @return list<ALineOfWhatAChangeComesTo> */
    private function asked(WhatAChangeAsksFor $ask): array
    {
        return [
            $this->line('config.findings.asks_for', ['what' => $ask->what], aside: false),
            $this->words($ask->because, aside: true),
            $ask->where(
                kept: fn(string $setting): ALineOfWhatAChangeComesTo => $this->line('config.findings.kept_in', ['setting' => $setting], aside: true),
                obtained: fn(): ALineOfWhatAChangeComesTo => $this->line('config.findings.to_obtain', [], aside: true),
            ),
        ];
    }

    private function side(WhatASettingHolds $holds, string $held, string $empty, string $withheld): ALineOfWhatAChangeComesTo
    {
        return $holds->either(
            shown: fn(string $value): ALineOfWhatAChangeComesTo => $this->line($value === '' ? $empty : $held, ['value' => $value], aside: true),
            withheld: fn(): ALineOfWhatAChangeComesTo => $this->line($withheld, [], aside: true),
        );
    }

    /** @param array<string, int|string> $with */
    private function line(string $said, array $with, bool $aside): ALineOfWhatAChangeComesTo
    {
        return new ALineOfWhatAChangeComesTo(said: $said, with: $with, aside: $aside);
    }

    private function words(string $words, bool $aside): ALineOfWhatAChangeComesTo
    {
        return new ALineOfWhatAChangeComesTo(said: 'config.findings.in_its_words', with: ['words' => $words], aside: $aside);
    }
}
