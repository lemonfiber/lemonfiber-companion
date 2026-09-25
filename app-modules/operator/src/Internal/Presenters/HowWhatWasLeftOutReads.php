<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Operator\Internal\ViewModels\AServiceLeftOutAsShown;

/** Services and forms the stack named, and the services it left out, as rows and names. */
final readonly class HowWhatWasLeftOutReads
{
    /** @return list<AServiceLeftOutAsShown> */
    public function of(TheServicesLeftOut $leftOut): array
    {
        $shown = [];

        foreach ($leftOut as $service) {
            $shown[] = new AServiceLeftOutAsShown($service->name(), $service->needs()->saidOnTheScreen(), $this->forms($service->askedBy()));
        }

        return $shown;
    }

    /** @return list<string> */
    public function forms(Forms $forms): array
    {
        $named = [];

        foreach ($forms as $form) {
            $named[] = $form->named();
        }

        return $named;
    }

    /** @return list<string> */
    public function services(Services $services): array
    {
        $named = [];

        foreach ($services as $service) {
            $named[] = $service->named();
        }

        return $named;
    }
}
