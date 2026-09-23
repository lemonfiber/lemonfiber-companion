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
    'turning_down' => ':title afwijzen',
    'turning_down_owes' => ':who ziet wat je hier schrijft, dus zeg genoeg zodat diegene het niet hoeft te komen vragen.',
    'reason_label' => 'Waarom niet',
    'reason_placeholder' => 'Deze maand is er geen ruimte voor',
    'reason_is_shown' => 'Zichtbaar voor wie erom vroeg.',
    'turn_it_down' => 'Afwijzen',
    'never_mind' => 'Laat maar',
    'nothing_asked' => 'Niemand heeft iets gevraagd.',
    'nothing_asked_action' => 'Wat het huishouden vraagt, verschijnt hier zodra iemand iets aanvraagt.',
    'asked_for' => 'Wat het huishouden vroeg',
    'refused_because' => 'Afgewezen: :reason',
    'refused_at' => 'Afgewezen op :when',
    'yours' => 'Wat je kunt aanvragen',
    'nothing_owed' => 'Er is hier niets om je te vertellen.',
    'nothing_owed_action' => 'Deze machine zegt niets over wat je kunt aanvragen.',
    'ask_again' => 'Opnieuw vragen',
    'back_to_the_machine' => 'Terug naar de machine',

    // Wat de machine zegt dat dit lid kan kijken. Een lege plank en een
    // bibliotheek die niet bereikt kon worden zijn twee verschillende
    // antwoorden, en de tweede wordt nooit als de eerste getoond.
    'shelf' => 'Wat je kunt kijken',
    'shelf_is_empty' => 'Er staat niets op je plank.',
    'shelf_is_empty_action' => 'Wat het huishouden voor je toevoegt, verschijnt hier.',
    'shelf_is_out_of_reach' => 'Je bibliotheek kon niet worden bereikt.',
    'shelf_is_out_of_reach_action' => 'De machine antwoordde, maar kon niet lezen wat er op je plank staat.',
    'medium' => [
        'film' => 'Film',
        'series' => 'Serie',
        'other' => 'Overig',
    ],
];
