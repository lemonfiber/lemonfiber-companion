<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function implode;
use function is_string;
use function view;

/** A title's genres on one line, in the media server's words, parted the way the reader's language parts a list. */
final class Genres extends Component
{
    /** What parts one genre from the next. */
    private const string BETWEEN = 'household.title.between_genres';

    /** The line, as it is drawn, or empty where there are none. */
    public readonly string $said;

    /** @param list<string> $genres */
    public function __construct(Translator $catalogue, array $genres)
    {
        $between = $catalogue->get(self::BETWEEN);
        $this->said = implode(is_string($between) ? $between : self::BETWEEN, $genres);
    }

    public function render(): View
    {
        return view('household::components.genres');
    }
}
