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

    // De ingang, vanaf het scherm van de machine zelf. Het noemt de vraag
    // en niet het mechanisme: een operator weet wat een herstart is en
    // hoeft niet te weten wat een launch agent is om dit te willen.
    'what_keeps_running' => 'Wat een herstart overleeft',

    // Hoeveel er geïnstalleerd zijn en niet draaien. Voor de rijen genoemd,
    // om dezelfde reden als bij het vastgelopen-scherm: wie dit de ochtend na
    // een herstart opent, zou ze niet moeten hoeven tellen.
    'did_not_come_back' => '{1} Eén ding kwam niet terug|[2,*] :count dingen kwamen niet terug',

    // Alleen een wees heeft er een, en die noemt het programma en niet de
    // service — het verschil tussen *dit draait niet* en *het bestand dat het
    // draait is er niet meer*.
    'missing_program' => 'Het programma dat het draait is weg: :program',

    // Een machine die niets draaiend houdt. Een eigen zin in plaats van een
    // lege lijst, en iets anders dan de machine die dit product niet kan
    // instellen — die draagt een instructie.
    'keeps_nothing_running' => 'Hier overleeft niets een herstart',
    'keeps_nothing_running_action' => 'Stel er een in op de machine, dan verschijnt die hier.',

    // Waarmee de machine dingen draaiend houdt terwijl niemand is ingelogd.
    // Bij naam genoemd in plaats van omschreven: een operator die `launchd`
    // leest kan ernaar zoeken, en een zin over *het servicebeheer van het
    // systeem* geeft ze niets om in te tikken.
    'keeps-running' => [
        'launchd' => 'launchd, in je eigen inlogsessie',
        'systemd' => 'systemd, in je eigen sessie',
        'unsupported' => 'Deze machine heeft geen servicebeheer dat lemonfiber instelt',
    ],

    // Eén commando aan deze machine geven om draaiend te houden, of het
    // terugnemen. Elke knop noemt zijn commando, zodat twee regels nooit
    // dezelfde woorden dragen.
    'handing_over' => [
        'install' => ':name draaiend houden op deze machine',
        'remove' => ':name niet langer draaiend houden',
    ],
    'handing_over_asked' => [
        'install' => ':name draaiend houden op deze machine?',
        'remove' => ':name niet langer draaiend houden?',
    ],
    'handing_over_means' => [
        'install' => 'Het servicebeheer van de machine wordt gevraagd het te draaien. De stack zegt daarna of het gestart is en waar het wegschrijft wat het zegt.',
        'remove' => 'Het draait dan alleen zolang een terminal het vasthoudt, en elk bestand dat de installatie maakte wordt teruggenomen.',
    ],

    // Wat ervan kwam, zoals de stack het zei. De kop noemt wat gevraagd werd en
    // zegt nooit dat het lukte: of het commando draait, zegt de stand.
    'handed_over' => [
        'heading' => [
            'install' => 'Gevraagd :name draaiend te houden',
            'remove' => 'Gevraagd :name niet langer draaiend te houden',
            'did_not' => ':name is niet overgedragen',
        ],
        'rehearsed' => 'Een repetitie: er is niets aan deze machine veranderd.',
        'stands' => 'Hoe het er nu voor staat: :standing',
        'started' => 'Het is gestart.',
        'not_started' => 'Het is niet gestart.',
        'writes_to' => 'Wat het zegt wordt weggeschreven naar :output',
        'writes_unsaid' => 'De stack zei niet waar wordt weggeschreven wat het zegt.',
        'touched' => [
            'install' => 'Geschreven: :file',
            'remove' => 'Teruggenomen: :file',
        ],
        'would_touch' => [
            'install' => 'Zou worden geschreven: :file',
            'remove' => 'Zou worden teruggenomen: :file',
        ],
        'touched_nothing' => [
            'install' => 'Er is geen bestand geschreven.',
            'remove' => 'Er was niets om terug te nemen.',
        ],
    ],

    // Hoe ver een wijziging van de stack terug te draaien is.
    'reversal' => [
        'whole' => 'Kan helemaal worden teruggedraaid',
        'partial' => 'Kan maar gedeeltelijk worden teruggedraaid',
        'none' => 'Kan niet worden teruggedraaid',
    ],

    // Wat de machine aan zichzelf heeft veranderd.
    'record' => [
        'road_in' => 'Wat hier is veranderd',
        'horizon' => 'Wat bewaard wordt: :horizon.',
        'by' => ':operation, aan :target',
        'alongside' => '{1} Op zichzelf gedaan|[2,*] Een van :count wijzigingen die samen zijn gedaan',
        'stops_short' => 'Stopt eerder omdat: :because',
        'instead' => 'In plaats daarvan: :instead',
        'nothing_changed' => 'Er is niets veranderd',
        'clock_unreadable' => 'Op een moment dat de machine niet kon vertellen',
    ],
    'origins' => [
        'road_in' => 'Waar dit vandaan komt',
        'as_declared' => 'Zoals deze machine het opgeeft. Niets hiervan wordt bij de projecten zelf opgezocht.',
        'runs' => 'Draait :image, vastgezet op :pinned',
        'licence' => 'Licentie: :licence',
        'upstream' => 'Gebouwd vanuit :upstream',
        'nothing_declared' => 'Deze machine geeft geen diensten op',
    ],
    'outbound' => [
        'road_in' => 'Wat deze machine verlaat',
        'asks_for' => [
            'registry' => 'Images van diensten ophalen',
            'guides' => 'De kwaliteitsgidsen controleren',
            'echo' => 'Het publieke adres van deze machine opzoeken',
            'indexer' => 'Een indexersleutel controleren',
            'usenet' => 'Een Usenet-login controleren',
            'household' => 'Een huisgenoot iets laten weten',
            'updates' => 'Kijken of er een nieuwere lemonfiber is',
        ],
        'allowed' => [
            'allowed' => 'Toegestaan door de instellingen van deze machine',
            'switched_off' => 'Uitgeschakeld',
        ],
        'ours' => [
            'heading' => 'Wat lemonfiber zelf verstuurt',
            'sends' => 'Verstuurt: :sends',
            'goes_to' => 'Naar :destination',
            'nowhere' => 'Er is niets ingesteld om te bereiken',
            'switch' => 'Uit te zetten met :switch',
            'cost' => 'Als het uit staat: :cost',
            'none' => 'lemonfiber verstuurt zelf niets',
        ],
        'theirs' => [
            'heading' => 'Wat de diensten versturen',
            'reaches' => 'Bereikt :destination',
            'reaches_nothing' => 'Bereikt niets',
            'unrecorded' => 'lemonfiber weet niet wat deze dienst bereikt',
            'none' => 'Geen enkele dienst hier verstuurt iets',
            'origin' => [
                'bundled' => 'Een eigen dienst van de stack',
                'operator' => 'Een hier toegevoegde dienst',
                'plugin' => 'Meegebracht door de plug-in :named',
                'unknown' => 'Niemand kon zeggen waar deze dienst vandaan komt — :why',
                'overridden' => 'Gewijzigd door de plug-in :named',
                'orphaned' => 'Meegebracht door de plug-in :named, die niet meer is geïnstalleerd',
                'legend' => 'Een dienst waarbij staat waar hij vandaan komt, is geen eigen dienst van de stack; alle andere wel.',
            ],
        ],
    ],
    'alerts' => [
        'road_in' => 'Waarover je bericht krijgt',
        'preset' => 'Voorinstelling: :preset',
        'set_apart' => 'Apart gezet van de voorinstelling',
        'heard' => [
            'heard' => 'Je hoort hierover, wat de voorinstelling ook zegt',
            'silenced' => 'Stil gehouden, wat de voorinstelling ook zegt',
        ],
        'nothing_set_apart' => 'Niets is apart gezet; elke gebeurtenis volgt de voorinstelling',
        'changed_at_the_machine' => 'Aan te passen op de machine, niet hier',
    ],
    'itself' => [
        'road_in' => 'Welke lemonfiber dit is',
        'running' => 'lemonfiber :version',
        'installed' => [
            'homebrew' => 'Geïnstalleerd met Homebrew',
            'scoop' => 'Geïnstalleerd met Scoop',
            'winget' => 'Geïnstalleerd met winget',
            'cargo' => 'Geïnstalleerd met cargo',
            'distribution' => 'Geïnstalleerd door de pakketbeheerder van het systeem',
            'installer' => 'Geïnstalleerd met de installer van lemonfiber',
            'elsewhere' => 'Op een andere manier geïnstalleerd',
            'untellable' => 'Hoe hij geïnstalleerd is kon niet bepaald worden',
        ],
        'owner' => 'Wordt bijgewerkt door :owner',
        'standing' => [
            'current' => 'Dit is de nieuwste versie',
            'update-available' => 'Er is een nieuwere versie',
            'managed-externally' => 'Een ander programma werkt deze kopie bij',
            'check-failed' => 'Of er een nieuwere versie is kon niet gecontroleerd worden',
        ],
        'offered' => 'Nieuwste: :version',
        'run_at_the_machine' => 'Om bij te werken, voer dit uit op de machine:',
        'not_the_services' => 'Dit is lemonfiber zelf. De services worden bijgewerkt vanaf hun eigen scherm.',
    ],

    // Wat de woorden van lemonfiber betekenen, zoals de woordenlijst van de stack ze uitlegt.
    'words' => [
        'road_in' => 'Wat de woorden van lemonfiber betekenen',
        'heading' => 'Wat de woorden van lemonfiber betekenen',
        'search_label' => 'Zoek een woord',
        'search_placeholder' => 'Een woord, of hoe een andere app het noemt',
        'also_called' => 'Ook wel: :names',
        'in_place' => ':word: :short',
        'more' => 'Meer over ‘:word’',
        'less' => 'Minder over ‘:word’',
        'nothing_matched' => 'Geen woord, en niets waarmee een woord ook wordt aangeduid, past daarbij',
        'none' => 'Deze machine legt geen woorden uit',
    ],

    'credentials' => [
        'road_in' => 'Wat hij bewaart om services binnen te laten',
        'heading' => 'Credentials',
        'state' => [
            'absent' => 'Ontbreekt: iets hier heeft hem nodig en hij is nooit opgegeven',
            'active' => 'Werkt',
            'stale' => 'Niet meer bevestigd sinds hij voor het laatst is geschreven',
            'invalid' => 'Geweigerd de laatste keer dat hij werd gebruikt',
            'rotating' => 'Wordt vervangen; de huidige werkt nog',
            'superseded' => 'Vervangen; de oude wacht om vernietigd te worden',
        ],
        'origin' => [
            'operator' => 'Door jou opgegeven, van een account elders',
            'service' => 'De service heeft hem zelf gemaakt',
            'lemonfiber' => 'Door lemonfiber gemaakt',
        ],
        'used_by' => 'Gebruikt door',
        'used_by_nothing' => 'Niets gebruikt hem',
        'none' => 'Deze machine bewaart geen credentials',
        'protection' => [
            'heading' => 'Hoe ze bewaard worden',
            'against' => 'Dit beschermt tegen:',
            'not_against' => 'Dit beschermt niet tegen:',
            'nothing_listed' => 'Niets opgesomd',
        ],
        'at_the_machine' => 'Een credential wordt op de machine ingesteld of vervangen, niet hier.',
    ],
    'clients' => [
        'road_in' => 'Met welke app kijken',
        'heading' => 'Waarop kijken',
        'support' => [
            'good' => 'Goed ondersteund',
            'workable' => 'Werkt, met iets om vooraf te weten',
            'poor' => 'Slecht ondersteund',
            'fallback' => 'Werkt overal, niets te installeren',
        ],
        'instead' => 'In plaats daarvan: :instead',
        'straining' => 'Afspelen kan hier moeite hebben met de voorinstelling :preset',
        'no_devices' => 'Er staan geen apparaten in de lijst',
        'trouble' => 'Als het niet werkt',
        'no_causes' => 'Hierachter staat niets opgesomd',
        'no_trouble' => 'Er staat niets opgesomd voor als het niet werkt',
    ],
    'front_door' => [
        'road_in' => 'Waar het huishouden binnenkomt',
        'standing' => [
            'established' => 'De voordeur staat open',
            'library-only' => 'De bibliotheek is de voordeur; hier valt niets aan te vragen',
            'unreachable' => 'De voordeur antwoordt niet',
            'stranded' => 'De voordeur antwoordt, en geen ander apparaat kan verteld worden waar hij is',
            'none' => 'Niets hier staat open voor het huishouden',
        ],
        'chosen' => [
            'derived' => 'Afgeleid van wat deze stack draait; niemand heeft hem gekozen',
            'named' => 'Door jou gekozen: :named',
            'refused' => 'Je koos :named, en dat werd geweigerd: :because',
        ],
        'facing' => [
            'asking' => 'Waar iets aanvragen begint',
            'watching' => 'De bibliotheek, waar bekeken wordt wat binnenkwam',
            'shelf' => 'Eén soort media, bereikt vanuit de bibliotheek',
            'operators' => 'Een overzicht van elke service, ook die het huishouden niet hoort te zien',
            'carriage' => 'Hoe de andere bereikt worden',
            'unstated' => 'Open voor het huishouden, en niets zegt wat het voor hen is',
        ],
        'no_address' => 'Deze machine zei niet waar hij bereikt wordt',
        'beside' => 'Wat ze verder kunnen bereiken',
        'nothing_beside' => 'Verder staat niets open voor het huishouden',
    ],

    // Iemand binnenvragen: wat een uitnodiging geeft, haar versturen, haar
    // doorgeven, en iemand een nieuw wachtwoord laten kiezen.
    'invitation' => [
        'road_in' => 'Iemand binnenvragen',
        'name' => 'Hun naam',
        'name_is' => 'De naam waarmee ze inloggen',
        'libraries' => 'Bibliotheken',
        'libraries_are' => 'Gescheiden door een komma, zoals deze stack ze noemt. Laat leeg voor elke bibliotheek',
        'age' => 'Leeftijdsgrens',
        'age_is' => 'Wat boven deze leeftijd is gekeurd wordt tegengehouden. Laat leeg voor geen grens',
        'unrated_is' => 'Alles zonder keuring: :choice',
        'unrated' => [
            'held-back' => 'wordt voor hen tegengehouden',
            'let-through' => 'wordt voor hen doorgelaten',
            'left_to_the_stack' => 'laat deze stack beslissen',
        ],
        'hold_unrated_back' => 'Houd alles zonder keuring tegen',
        'let_unrated_through' => 'Laat alles zonder keuring door',
        'leave_unrated_to_the_stack' => 'Laat materiaal zonder keuring aan deze stack over',
        'needs_a_name' => 'Zeg voor wie dit is: een uitnodiging heeft een naam nodig',
        'age_is_a_number' => 'Een leeftijdsgrens is een heel aantal jaren',
        'what_would_it_grant' => 'Bekijk wat uitnodigen zou doen',
        'working' => 'Deze stack is ermee bezig',
        'no_outcome' => 'Deze stack heeft geen antwoord meer op wat over :name gevraagd werd. Het huishoudscherm zegt wie er binnen is',
        'refused' => 'Deze stack wilde dit niet doen voor :name',
        'rehearsed' => 'Dit is wat :name uitnodigen zou doen. Er is nog niets aangemaakt',
        'standing' => [
            'made' => 'Een nieuw account voor :name',
            'waiting' => 'Er staat al een uitnodiging voor :name. Die is het om te sturen, en er komt geen tweede',
            'joined' => ':name is al in het huishouden. Er wordt niets verstuurd',
            'reset' => 'Het wachtwoord is van het account van :name gehaald. Ze kiezen een nieuw op het adres',
        ],
        'grants' => 'Wat het hen laat doen',
        'grants_nothing' => 'De stack zei niet wat het toestaat',
        'every_library' => 'Elke bibliotheek',
        'limited_to' => 'Tot en met :limit',
        'no_limit' => 'Geen leeftijdsgrens',
        'asking' => [
            'made' => 'Ze kunnen om dingen vragen, en kijken',
            'not-yet' => 'Ze kunnen kijken, en nog nergens om vragen: de verzoekservice weet het nog niet, en de volgende ronde vertelt het',
            'not-tried' => 'De verzoekservice is niet gevraagd: dit is een generale repetitie, of deze stack heeft er geen',
        ],
        'lapses' => '{1} Het staat :count uur. Neemt niemand het voor die tijd aan, dan wordt het ingetrokken en het account ook|[0,*] Het staat :count uur. Neemt niemand het voor die tijd aan, dan wordt het ingetrokken en het account ook',
        'send' => 'Nodig :name uit zoals getoond',
        'to_hand_over' => 'Het adres om door te geven',
        'code' => 'Het adres als code die een andere telefoon kan scannen',
        'no_code' => 'Dit adres kon niet als code getekend worden. Geef het als tekst door',
        'pass_on' => 'Doorgeven',
        'passed_on' => 'Aan het delen van deze telefoon gegeven. Waar het daarna heen gaat is aan jou',
        'not_passed_on' => 'Deze telefoon bood geen manier om het door te geven. Er is niets verstuurd, en het adres staat hierboven om op een andere manier door te geven',
        'covering' => '{1} Je bent uitgenodigd in het huishouden op :stack, als :name. Open dit adres om je wachtwoord te kiezen. Het staat :count uur.|[0,*] Je bent uitgenodigd in het huishouden op :stack, als :name. Open dit adres om je wachtwoord te kiezen. Het staat :count uur.',
        'would_withdraw' => 'Uitnodigingen die niemand aannam, die dit zou intrekken',
        'withdrew' => 'Uitnodigingen die niemand aannam, onderweg ingetrokken',
        'nobody_withdrawn' => 'Geen',
        'start_again' => 'Opnieuw beginnen',
        'who_is_in' => 'Wie er al binnen is',
        'ask_who_is_in_again' => 'Bekijk opnieuw wie er binnen is',
        'member' => [
            'joined' => 'In het huishouden',
            'still_invited' => 'Uitgenodigd, en nog niet aangenomen',
        ],
        'nobody_in' => 'Nog niemand heeft een account op de mediaserver',
        'would_take_it_off' => 'Laat :name een nieuw wachtwoord kiezen',
        'taking_it_off_means' => 'Het wachtwoord dat :name nu heeft werkt niet meer, en ze kiezen een nieuw op het adres dat je hiermee krijgt. Jij ziet het nooit en stelt het nooit in',
        'take_it_off' => 'Haal het wachtwoord van het account van :name',
        'never_mind' => 'Laat het zoals het is',
    ],

    'room' => [
        'road_in' => 'Hoe vol deze machine is',
        'level' => [
            'unknown' => 'Hoe vol hij is kon niet gelezen worden',
            'ample' => 'Ruim voldoende plek',
            'advisory' => 'Minder plek dan prettig is',
            'warning' => 'Loopt vol met wat er onderweg is',
            'critical' => 'Bijna vol',
            'exhausted' => 'Vol',
        ],
        'halted' => 'Nieuwe downloads zijn gestopt zodat de services nog kunnen schrijven',
        'holds' => [
            'data' => 'Waar de media en downloads staan',
            'services' => 'Waar de services hun instellingen en databases bewaren',
        ],
        'free' => ':figure :unit vrij',
        'free_unread' => 'Hoeveel er vrij is kon niet gelezen worden',
        'limit' => 'Van :figure :unit',
        'committed' => ':figure :unit onderweg',
        'projected' => ':figure :unit vrij als dat binnen is',
        'as_of' => 'Zoals laatst gelezen :ago',
        'no_volumes' => 'De stack noemt geen volume dat hij bewaakt',
        'account' => 'Waar de plek naartoe ging',
        'about' => [
            'tree' => ':tree',
            'landing' => 'Downloads die nog geschreven worden',
            'seeding' => 'Downloads die nog geseed worden',
            'orphaned' => 'Downloads die geen service heeft opgenomen',
            'extracted' => 'Archieven die al uitgepakt zijn',
            'services' => 'De eigen instellingen en databases van de services',
            'unmanaged' => 'Wat je met rust wilde laten',
        ],
        'occupies' => 'Neemt :figure :unit in',
        'unshared' => 'Zou :figure :unit innemen als niets gedeeld werd',
        'reclaim' => [
            'by_losing_content' => 'Dit terugkrijgen betekent iets kwijtraken dat je wilde houden',
            'in_progress' => 'Niets terug te krijgen: het wordt nu geschreven',
            'at_the_cost_of_ratio' => 'Terug te krijgen, ten koste van je positie bij de trackers',
            'the_easy_win' => 'Terug te krijgen, en het kost niets',
            'already_have_it' => 'Terug te krijgen: de uitgepakte kopie is de gebruikte',
            'marginally' => 'Een beetje terug te krijgen, zelden de moeite',
            'you_said_not' => 'Niet terug te krijgen, omdat je dat zei',
        ],
        'nothing_accounted' => 'Niets neemt plek in',
        'downloads' => 'Afgeronde downloads op deze machine',
        'takes' => 'Neemt :figure :unit in',
        'standing' => [
            'never_imported' => 'Nooit in een bibliotheek opgenomen',
            'seeding' => 'Wordt nog geseed',
            'left_alone' => 'Je wilde dit met rust laten',
        ],
        'ratio' => 'Ratio :ratio',
        'no_ratio' => 'Geen ratio: er is niets gedownload om door te delen',
        'no_downloads' => 'Er staan geen afgeronde downloads op deze machine',
        'at_the_machine' => 'Verwijderen gebeurt op de machine, niet vanaf hier',
    ],

    'keeps' => [
        'road_in' => 'Wat deze machine bewaart',
        'roots' => 'Waar alles bewaard wordt',
        'no_roots' => 'De stack noemt geen plek waar hij iets bewaart',
        'kept' => 'Wat de stack bewaart',
        'nothing_kept' => 'De stack bewaart hier niets',
        'secret' => [
            'secret' => 'Bevat een geheim, dat hier nooit getoond wordt',
            'plain' => 'Bevat geen geheim',
        ],
        'beside' => 'Hier, en niet van de stack',
        'nothing_beside' => 'Niets hier is van iemand anders',
        'copies' => 'Kopieën van de stack',
        'no_copies' => 'Er is nog geen kopie gemaakt',
        'copies_unread' => 'De kopieën konden niet opgesomd worden',
        'at_the_machine' => 'Kopieën worden op de machine gemaakt en teruggezet, niet vanaf hier',
    ],

    'line' => [
        'road_in' => 'Hoe de lijn gedeeld wordt',
        'restraint' => [
            'unlimited' => 'Niets houdt de stack tegen',
            'limited' => 'De stack is aan een limiet gebonden',
            'scheduled-active' => 'Het huis is wakker, dus de stack wordt afgeremd',
            'scheduled-quiet' => 'Het huis slaapt, dus de lijn is van de stack',
            'overridden' => 'De limieten zijn tijdelijk opgeheven',
            'cap-warning' => 'Dicht bij het maandplafond',
            'cap-exceeded' => 'Het maandplafond is bereikt',
        ],
        'down' => 'Download: :says',
        'up' => 'Upload: :says',
        'upload_cost' => 'De upload afremmen kost: :costs',
        'capacity' => 'Wat de lijn aankan',
        'carries' => ':down :down_unit omlaag, :up :up_unit omhoog',
        'rate' => [
            'kilobits' => 'kbit/s',
            'megabits' => 'Mbit/s',
            'gigabits' => 'Gbit/s',
        ],
        'measured' => [
            'declared' => 'Zoals opgegeven, niet gemeten',
            'observed' => 'Zoals de stack het heeft zien gaan',
        ],
        'tunnel' => [
            'through' => 'Gemeten door de privétunnel waar het verkeer van de stack doorheen gaat',
            'beside' => 'Gemeten naast de privétunnel waar het verkeer van de stack doorheen gaat',
        ],
        'unmeasured' => 'Niets heeft de lijn gemeten',
        'monthly_cap' => 'Maandplafond',
        'capped_at' => ':figure :unit per maand',
        'cap' => [
            'pause' => 'Bij het bereiken stopt het ophalen tot de nieuwe maand',
            'throttle' => 'Bij het bereiken gaat het ophalen langzaam door, zodat half werk af kan',
            'continue' => 'Bij het bereiken verandert er niets; het ophalen gaat door',
        ],
        'month' => [
            'within' => 'Deze maand zit er ruim onder',
            'warning' => 'Deze maand zit er dichtbij',
            'exceeded' => 'Deze maand heeft het bereikt',
        ],
        'uncapped' => 'Er is geen plafond opgegeven',
        'untouched' => 'Buiten elke limiet',
        'nothing_untouched' => 'Niets valt buiten de limieten',
        'no_cautions' => 'De stack heeft niets toe te voegen over deze meting',
        'changed_at_the_machine' => 'Limieten en plafonds worden op de machine aangepast, niet hier',
    ],
];
