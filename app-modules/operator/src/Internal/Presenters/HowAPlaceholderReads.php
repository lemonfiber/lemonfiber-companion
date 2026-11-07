<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\TheFilesReshipped;
use Modules\Kernel\Api\ThePlaceholders;
use Modules\Operator\Internal\ViewModels\AFileReshippedAsShown;
use Modules\Operator\Internal\ViewModels\APlaceholderAsShown;

/** Starting files' placeholders, as the rows a plugin's account draws: by the secret's id and its form, never a value. */
final readonly class HowAPlaceholderReads
{
    /**
     * Each placeholder, in the file's order.
     *
     * @return list<APlaceholderAsShown>
     */
    public function these(ThePlaceholders $placeholders): array
    {
        $shown = [];

        foreach ($placeholders as $placeholder) {
            $shown[] = new APlaceholderAsShown($placeholder->secret(), $placeholder->form()->saidOnTheScreen());
        }

        return $shown;
    }

    /**
     * Each starting file an update changes the placeholders of.
     *
     * @return list<AFileReshippedAsShown>
     */
    public function reshipped(TheFilesReshipped $files): array
    {
        $shown = [];

        foreach ($files as $file) {
            $shown[] = new AFileReshippedAsShown($file->path(), $this->these($file->was()), $this->these($file->becomes()));
        }

        return $shown;
    }
}
