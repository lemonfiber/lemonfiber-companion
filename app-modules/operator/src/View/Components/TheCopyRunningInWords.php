<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;

/** The sentences a running copy is said in, the same on the panel and on the line that leads to it. */
final readonly class TheCopyRunningInWords
{
    private const string WRITTEN = 'stacks.copy.running.written';

    private const string SO_FAR = 'stacks.copy.running.so_far';

    public function __construct(private Translator $catalogue, private ACopyRunningAsShown $copy) {}

    /** What is running and what it is a copy of, or that none runs, or that the app cannot tell. */
    public function title(): string
    {
        $scope = $this->copy->scope;

        return $this->said($this->copy->said, $scope instanceof AScopeAsShown ? ['scope' => $this->said($scope->said, $scope->with)] : []);
    }

    /** How much of how much is written. */
    public function written(): string
    {
        $written = $this->copy->written;
        $whole = $this->copy->whole;

        if (!$written instanceof ASizeAsShown || !$whole instanceof ASizeAsShown) {
            return '';
        }

        return $this->said(self::WRITTEN, [
            'written' => (string) $written->figure,
            'written_unit' => $this->said($written->unit),
            'whole' => (string) $whole->figure,
            'whole_unit' => $this->said($whole->unit),
        ]);
    }

    /** Whether it is writing, slowed on purpose to the limit, or stuck and for how long. */
    public function moves(): string
    {
        $limit = $this->copy->limit;

        return $this->catalogue->choice($this->copy->moves, $this->copy->count, $limit instanceof ASizeAsShown ? ['limit' => (string) $limit->figure, 'limit_unit' => $this->said($limit->unit)] : []);
    }

    /** How long ago it started. */
    public function started(): string
    {
        return $this->catalogue->choice($this->copy->started, $this->copy->since);
    }

    /** How far it has got in whole percent and how it moves, or how it moves alone where the whole is nothing. */
    public function soFar(): string
    {
        $percent = $this->copy->percent;

        return $percent === null ? $this->moves() : $this->said(self::SO_FAR, ['percent' => (string) $percent, 'moves' => $this->moves()]);
    }

    /** @param array<string, string> $with */
    private function said(string $key, array $with = []): string
    {
        $said = $this->catalogue->get($key, $with);

        return is_string($said) ? $said : $key;
    }
}
