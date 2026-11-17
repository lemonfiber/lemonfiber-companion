<?php

declare(strict_types=1);

return [
    'title' => 'Geplande taken',
    'between_runs' => 'Deze draaien op de tijden die hun instellingen noemen en stoppen dan tot de volgende. Tussen twee keer niet draaien hoort zo.',
    'nothing' => 'Niets op deze stack draait volgens een schema',
    'ran' => 'Laatst gedraaid :ago',
    'running' => 'Er draait nu een keer',
    'not_fired' => 'De planner van deze machine heeft de taak niet, dus de tijden worden niet afgevuurd',
    'skipped' => '{1} Eén tijd viel terwijl een keer nog bezig was, en is overgeslagen|[2,*] :count tijden vielen terwijl een keer nog bezig was, en zijn overgeslagen',
    'log' => 'De laatste regels',
    'no_log' => 'Er zijn geen regels geschreven',
    'health' => [
        'not-run' => 'Nog niet gedraaid',
        'succeeded' => 'De laatste keer is gelukt',
        'failed' => 'De laatste keer is mislukt',
    ],
    'exit' => [
        'code' => 'Gestopt met code :code',
        'none' => 'Gestopt zonder code',
    ],
    'took' => [
        'minutes' => '{0} Het duurde minder dan een minuut|{1} Het duurde een minuut|[2,*] Het duurde :count minuten',
        'hours' => '{1} Het duurde een uur|[2,*] Het duurde :count uur',
        'days' => '{1} Het duurde een dag|[2,*] Het duurde :count dagen',
    ],
];
