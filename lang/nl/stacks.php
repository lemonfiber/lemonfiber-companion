<?php

declare(strict_types=1);

return [
    // Wat er tussen een langlopende opdracht en de machine staat, in de woorden
    // van de operator en niet die van de draad. Twee van de zes zijn de reden
    // dat deze groep is uitgeschreven: *installed-unverified* leest als draait
    // en is het niet, en *unsupported* leest als uit en is niet aan te zetten.
    'hosting' => [
        'not-hosted' => 'Draait alleen zolang er een terminal openstaat',
        'hosted' => 'Komt terug na een herstart',
        'installed-unverified' => 'Geïnstalleerd — de machine zei niet of het draait',
        'stopped' => 'Geïnstalleerd, en draait niet',
        'orphaned' => 'Geïnstalleerd voor een programma dat er niet meer is',
        'unsupported' => 'Niet beschikbaar op deze machine',
    ],

    // Waarmee de machine dingen draaiend houdt terwijl niemand is ingelogd.
    // Bij naam genoemd in plaats van omschreven: een operator die `launchd`
    // leest kan ernaar zoeken, en een zin over *het servicebeheer van het
    // systeem* geeft ze niets om in te tikken.
    'keeps-running' => [
        'launchd' => 'launchd, in je eigen inlogsessie',
        'systemd' => 'systemd, in je eigen sessie',
        'unsupported' => 'Deze machine heeft geen servicebeheer dat lemonfiber instelt',
    ],
];
