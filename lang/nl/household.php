<?php

declare(strict_types=1);

return [
    // Where a request stands, in the household's own words rather than the
    // wire's. Only the first of these is required; the rest are here because a
    // screen that showed only what is waiting would leave somebody wondering
    // what became of the thing they asked for last week.
    'waiting-for-approval' => 'Wacht op je akkoord',
    'declined' => 'Afgewezen',
    'failed' => 'Ophalen is mislukt',
    'getting' => 'Wordt opgehaald',
    'partly-here' => 'Gedeeltelijk binnen',
    'here' => 'Binnen',
    'gone' => 'Niet meer aanwezig',
    // Een status die de aanvraagdienst meldde en waar lemonfiber geen woord
    // voor heeft. Die wordt getoond in plaats van geraden of weggelaten: de
    // aanvraag bestaat, hij is van iemand, en het enige wat niemand kan zeggen
    // is hoe ver hij is.
    'unnamed' => 'Status hier niet bekend',
    // The same states, said to the person who asked rather than to the
    // operator deciding. Only the first differs in meaning rather than in
    // wording: a member is not the one whose decision is waited on, and a
    // screen telling them so would ask them for something they cannot give.
    'asked' => [
        'waiting-for-approval' => 'Wacht op akkoord',
        'declined' => 'Afgewezen',
        'failed' => 'Ophalen is mislukt',
        'getting' => 'Onderweg',
        'partly-here' => 'Gedeeltelijk binnen',
        'here' => 'Binnen',
        'gone' => 'Niet meer aanwezig',
        // Hetzelfde, gezegd tegen degene die het vroeg. Ze zien hun eigen
        // aanvraag; wat niemand ze kan vertellen is hoe ver hij is.
        'unnamed' => 'Status hier niet bekend',
    ],
    'your_requests' => 'Wat je hebt aangevraagd',
    'nothing_asked_for' => 'Je hebt nog niets aangevraagd.',
    'waiting_count' => '{0} Niets wacht op je akkoord|{1} Eén verzoek wacht op je akkoord|[2,*] :count verzoeken wachten op je akkoord',
    'asked_by' => 'Gevraagd door :who',
    'size_measured' => ':size :unit',
    'size_guessed' => 'Ongeveer :size :unit',
    'size_unknown' => 'Grootte nog onbekend',
    'megabytes' => 'MB',
    'gigabytes' => 'GB',
    'terabytes' => 'TB',
    // A waiting request is approvable and refusable from here, and the sentence
    // is part of turning one down rather than something beside it.
    'approve' => 'Goedkeuren',
    'approve_that' => ':title goedkeuren',
    'turn_down' => 'Afwijzen',
    'turn_down_that' => 'Wijs :title af',
    'turning_down' => ':title afwijzen',
    'turning_down_owes' => ':who ziet wat je hier schrijft, dus zeg genoeg zodat diegene het niet hoeft te komen vragen.',
    'reason_label' => 'Waarom niet',
    'reason_placeholder' => 'Deze maand is er geen ruimte voor',
    'reason_is_shown' => 'Zichtbaar voor wie erom vroeg.',
    'turn_it_down' => 'Afwijzen',
    'never_mind' => 'Laat maar',
    'nothing_asked' => 'Niemand heeft iets gevraagd.',
    'nothing_asked_action' => 'Wat het huishouden vraagt, verschijnt hier zodra iemand iets aanvraagt.',
    'refused_because' => 'Afgewezen: :reason',
    'refused_at' => 'Afgewezen op :when',
    'tabs' => [
        'home' => 'Thuis',
        'search' => 'Zoeken',
        'requests' => 'Aanvragen',
        'profile' => 'Profiel',
    ],

    'nothing_owed' => 'Er is hier niets om je te vertellen.',
    'nothing_owed_action' => 'Het huis zegt niets over wat je kunt aanvragen.',
    'ask_again' => 'Opnieuw vragen',
    'ask_again_for_yours' => 'Opnieuw vragen naar wat je hebt aangevraagd',
    'needs_an_update' => 'Het huis heeft hiervoor een update nodig.',
    'needs_an_update_action' => 'Wie het huis beheert, kan het bijwerken.',

    'search_is_coming' => 'Zoeken vanaf je telefoon komt eraan.',
    'search_is_coming_action' => 'Tot die tijd staat wat je kunt kijken op Thuis, en wat je hebt aangevraagd onder Aanvragen.',

    'switch_house' => 'Ander huis',
    'your_houses' => 'Je huizen',
    'add_house' => 'Huis toevoegen',
    'open_house' => ':house openen',
    'current_house' => ':house, waar je nu bent',
    'app_settings' => 'App-instellingen',
    'remove_house' => 'Dit huis van de telefoon halen',
    'remove_house_confirm' => ':house van deze telefoon halen? Je kunt het later weer toevoegen. Bij het huis verandert niets.',
    'remove' => 'Weghalen',
    'keep_it' => 'Laten staan',
    'remove_house_refused' => 'De telefoon kon :house niet weghalen. Er is niets weggehaald.',

    // Welke taal een lid wil horen en lezen. Bewaard op deze telefoon, voor
    // hen, en nooit naar het huis gestuurd.
    'languages' => [
        'hear' => 'Geluid',
        'read' => 'Ondertiteling',
        'hear_original' => 'Zoals het gemaakt is',
        'no_subtitles' => 'Uit',
        'dutch' => 'Nederlands',
        'english' => 'Engels',
        'kept_on_this_phone' => 'Bewaard op deze telefoon. Een titel zonder jouw taal speelt zijn eigen geluid, zonder ondertiteling.',
    ],

    // Wat de machine zegt dat dit lid kan kijken. Een lege plank en een
    // bibliotheek die niet bereikt kon worden zijn twee verschillende
    // antwoorden, en de tweede wordt nooit als de eerste getoond.
    'shelf_is_empty' => 'Er staat niets op je plank.',
    'shelf_is_empty_action' => 'Wat het huishouden voor je toevoegt, verschijnt hier.',
    'shelf_is_out_of_reach' => 'Je bibliotheek kon niet worden bereikt.',
    'shelf_is_out_of_reach_action' => 'Het huis antwoordde, maar kon niet lezen wat er op je plank staat.',
    // Wat het bereiken van het huis in de weg stond, zoals een lid het hoort.
    'out_of_reach' => [
        'no_answer' => 'Het huis antwoordt nu niet.',
        'no_answer_action' => 'Controleer of deze telefoon op het thuisnetwerk zit en probeer het zo nog eens.',
        'name_not_found' => 'Deze telefoon kon het huis niet vinden.',
        'name_not_found_action' => 'Controleer of deze telefoon op het thuisnetwerk zit. Zo ja, vraag wie het huis beheert om deze telefoon opnieuw in te stellen.',
        'nothing_at_the_address' => 'Het huis is niet meer waar deze telefoon het de vorige keer vond.',
        'nothing_at_the_address_action' => 'Vraag wie het huis beheert om deze telefoon opnieuw in te stellen.',
        'connection_refused' => 'Het huis is nu dicht.',
        'connection_refused_action' => 'Vraag wie het huis beheert om het weer open te zetten.',
    ],
    'medium' => [
        'film' => 'Film',
        'series' => 'Serie',
        'episode' => 'Aflevering',
        'other' => 'Overig',
    ],

    'shelf' => [
        'carry_on' => 'Verder kijken',
        'new' => 'Nieuw in huis',
        'ready_for_you' => 'Klaar voor jou',
        'on_its_way' => 'Onderweg',
        'film' => 'Films',
        'series' => 'Series',
        'episode' => 'Afleveringen',
        'other' => 'Overig',
    ],

    'poster' => [
        'left' => 'Nog :minutes min',
        'part_way' => 'Halverwege',
        'reads_part_way' => ':title, halverwege',
        'above' => ':year · :kind',
        'above_undated' => ':kind',
        'reads' => ':title, :kind, :year',
        'reads_undated' => ':title, :kind',
        'reads_standing' => ':title, :standing',
    ],

    'hero' => [
        'above' => 'Nieuw in huis · :line',
        'reads' => 'Nieuw in huis: :reads',
        'more' => 'Meer',
        'more_named' => 'Meer over :title',
    ],

    'title' => [
        'play' => 'Afspelen',
        'play_named' => ':title afspelen',
        'nothing_to_play' => 'Er is in deze serie nog niets af te spelen.',
        'absent' => 'Deze titel staat niet op je plank. Misschien is hij het huis uit, of is het er geen die jij kunt kijken.',
        'to_home' => 'Naar Thuis',
        'runs_hours' => ':hours u :minutes min',
        'runs_minutes' => ':minutes min',
        'certificate' => 'Kijkwijzer :certificate',
        'released' => 'Uitgekomen op :day :month :year',
        'episode' => ':number. :title',
        'episode_unnumbered' => ':title',
        'between_genres' => ', ',
        'no_episodes' => 'Er zijn nog geen afleveringen in dit seizoen.',
    ],

    // Waarom Afspelen niet afspeelde, of stopte waar het niet verder kon,
    // gezegd als wat een lid eraan kan doen.
    'play' => [
        'cannot_be_played' => 'Dit kan nu niet worden afgespeeld. Laat het weten aan wie het huis beheert.',
        'no_player' => 'Afspelen kan niet op dit apparaat.',
        'out_of_reach' => 'Het huis is van hieruit niet te bereiken. Afspelen werkt als je thuis bent.',
        'not_the_house' => 'Dit is gestopt omdat iets anders dan het huis antwoordde. Laat het weten aan wie het huis beheert.',
        'not_on_this_device' => 'Dit apparaat kan deze niet afspelen.',
        'not_let_in' => 'Dit is gestopt omdat het huis het hier niet liet afspelen. Druk op Afspelen om het opnieuw te proberen.',
    ],

    'month' => [
        'january' => 'januari',
        'february' => 'februari',
        'march' => 'maart',
        'april' => 'april',
        'may' => 'mei',
        'june' => 'juni',
        'july' => 'juli',
        'august' => 'augustus',
        'september' => 'september',
        'october' => 'oktober',
        'november' => 'november',
        'december' => 'december',
    ],

    'preview' => [
        'marked' => 'Voorbeeld: wat een lid ziet',
        'about' => 'Dit ziet iemand die met de standaardinstellingen van het huishouden is uitgenodigd. Niemands eigen plank of aanvragen worden getoond.',
        'back' => 'Terug naar Switchboard',
        'ask' => 'Iets aanvragen',
        'cannot_ask' => 'Een voorbeeld kan niets aanvragen.',
        'cannot_play' => 'Een voorbeeld kan niets afspelen.',
    ],
];
