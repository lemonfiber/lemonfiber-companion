<?php

declare(strict_types=1);

return [
    // The credentials the machine holds to let services in. No line here
    // offers to set, change or show a value.
    'credentials' => [
        'heading' => 'Passwords',
        'state' => [
            'absent' => 'Missing: something here needs it and it was never supplied',
            'active' => 'Working',
            // Never drawn as broken: it has not been proven, which is different.
            'stale' => 'Not proven since it was last written',
            'invalid' => 'Refused the last time it was used',
            // Mid-change, and the old value still works.
            'rotating' => 'Being replaced; the current one still works',
            'superseded' => 'Replaced; the old one is waiting to be destroyed',
        ],
        'origin' => [
            'operator' => 'Yours to supply, from an account elsewhere',
            'service' => 'The service made it for itself',
            'lemonfiber' => 'lemonfiber made it',
        ],
        'used_by' => 'Used by',
        'used_by_nothing' => 'Nothing uses it',
        'none' => 'This machine holds no passwords',
        'protection' => [
            'heading' => 'How they are kept',
            'against' => 'This protects against:',
            'not_against' => 'This does not protect against:',
            'nothing_listed' => 'Nothing listed',
        ],
        'at_the_machine' => 'A password is set or replaced at the machine, not from here.',
    ],

    // Which app to watch on, device by device.
    'clients' => [
        'heading' => 'What to watch on',
        'support' => [
            'good' => 'Well served',
            'workable' => 'Works, with something to know first',
            'poor' => 'Poorly served',
            // An answer, not the absence of one.
            'fallback' => 'Works anywhere, nothing to install',
        ],
        'instead' => 'Instead: :instead',
        // Said of an app that is named and never the recommended path.
        'not_open_source' => 'Not open source',
        'straining' => 'Playback may struggle here with the :preset preset',
        'no_devices' => 'No devices are listed',
        'trouble' => 'When it does not work',
        'no_causes' => 'Nothing is listed behind this',
        'no_trouble' => 'Nothing is listed for when it does not work',
    ],

    // Where the household comes in, and what else they can reach.
    'front_door' => [
        'standing' => [
            'established' => 'The front door is open',
            'library-only' => 'The library is the front door; there is nothing here to ask for',
            'unreachable' => 'The front door is not answering',
            'stranded' => 'The front door is answering, and no other device can be told where it is',
            'none' => 'Nothing here is open to the household',
        ],
        'chosen' => [
            // Never drawn as anybody's decision.
            'derived' => 'Worked out from what this stack runs; nobody chose it',
            'named' => 'You chose it: :named',
            'refused' => 'You chose :named, and it was refused: :because',
        ],
        'facing' => [
            'asking' => 'Where asking for something begins',
            'watching' => 'The library, where what arrived is watched',
            'shelf' => 'One kind of media, reached from the library',
            'operators' => 'An index of every service, including ones the household should not see',
            'carriage' => 'How the others are reached',
            'unstated' => 'Open to the household, and nothing says what it is to them',
        ],
        'no_address' => 'This machine did not say where it is reached',
        'beside' => 'What else they can reach',
        'nothing_beside' => 'Nothing else is open to the household',
    ],

    // Connecting one member's device, from their card under who is in.
    'handoff' => [
        'title' => 'Connect a device',
        // The same link's name on each member's card, so each is told apart.
        'title_for' => 'Connect a device for :name',
        'show' => 'Show the code',
        'stands' => [
            'ready' => 'Code ready',
            'pending' => 'Waiting for them',
            'connected' => 'Signed in',
            'failed' => 'Did not work',
        ],
        'invite_them' => 'Invite them',
        'on_their_device' => 'On their device',
        'which_app' => 'Which app',
        'opens_at_this_server' => 'A link that opens :client at this server',
        'signed_in_devices' => 'Signed-in devices',
        'last_seen' => 'Last seen :when',
        'first_given' => 'Code first given :when',
    ],

    // Pairing another phone with this stack: a code it scans, or a line it types.
    'pairing' => [
        'heading' => 'Pair a phone',
        'what_it_is' => 'A code another phone scans to add this stack. It carries no password: that phone still signs in with yours.',
        'make' => 'Make a code',
        'no_code' => 'This could not be drawn as a code. Type the line instead',
        'or_type' => 'Or type this on the phone:',
        'compare' => 'When it is typed, the other phone shows this. Check that it matches.',
        'until' => 'It stops being good at :until.',
        'reaches' => 'The phone reaches this machine at :address.',
        'replaced_at_the_machine' => 'Its certificate is replaced at the machine, not from here.',
        'checked_differently' => 'This stack works out a different check code from this phone, so a phone that types this line cannot check it. Update lemonfiber or the app.',
        'expired' => 'This code has expired',
        'make_a_new_one' => 'Make a new one',
    ],

    // Asking somebody in: what an invitation grants, sending it, handing it
    // over, and letting somebody choose a new password.
    'invitation' => [
        'road_in' => 'Ask somebody in',
        'name' => 'Their name',
        'name_is' => 'The name they will sign in as',
        'libraries' => 'Libraries',
        'libraries_are' => 'Separated by commas, as this stack names them. Leave it empty for every library',
        'age' => 'Age limit',
        'age_is' => 'Things rated above this age are held back. Leave it empty for no limit',
        'unrated_is' => 'Anything with no rating: :choice',
        'unrated' => [
            'held-back' => 'held back from them',
            'let-through' => 'let through to them',
            'left_to_the_stack' => 'left to this stack to decide',
        ],
        'hold_unrated_back' => 'Hold it back',
        'let_unrated_through' => 'Let it through',
        'leave_unrated_to_the_stack' => 'Leave it to this stack',
        'needs_a_name' => 'Say who this is for: an invitation needs a name',
        'age_is_a_number' => 'An age limit is a whole number of years',
        'what_would_it_grant' => 'See what inviting them would do',
        'working' => 'This stack is working on it',
        'no_outcome' => 'This stack no longer has an answer for what was asked about :name. The household screen says who is in',
        'refused' => 'This stack would not do this for :name',
        'rehearsed' => 'This is what inviting :name would do. Nothing has been made yet',
        'standing' => [
            'made' => 'A new account for :name',
            'waiting' => 'An invitation for :name already stands. It is the one to send, and no second one is made',
            'joined' => ':name is already in the household. Nothing is sent',
            'reset' => 'The password on the account of :name is taken off. They choose a new one at the address',
        ],
        'grants' => 'What it lets them do',
        'grants_nothing' => 'The stack did not say what it grants',
        'every_library' => 'Every library',
        'limited_to' => 'Up to :limit',
        'no_limit' => 'No age limit',
        'asking' => [
            'made' => 'They can ask for things as well as watch',
            'not-yet' => 'They can watch, and cannot ask for anything yet: the request service has not been told, and the next run tells it',
            'not-tried' => 'The request service was not asked: this is a rehearsal, or this stack has none',
        ],
        'lapses' => '{1} It stands for :count hour. If nobody takes it up by then, it is withdrawn and the account with it|[0,*] It stands for :count hours. If nobody takes it up by then, it is withdrawn and the account with it',
        'send' => 'Invite :name as shown',
        'to_hand_over' => 'The address to hand over',
        'code' => 'Scan this to open the invitation and choose a password',
        'to_join' => 'The link that opens it in the app',
        'join_code' => 'Scan this with the lemonfiber app to join from that phone',
        'to_turn_down' => 'The address that turns it down',
        'decline_code' => 'Scan this to turn the invitation down',
        'no_code' => 'This address could not be drawn as a code. Hand it over as text',
        'pass_on' => 'Hand it over',
        'passed_on' => 'Handed to the sharing on this phone. Where it goes from there is up to you',
        'not_passed_on' => 'This phone would not offer a way to pass it on. Nothing was sent, and the address is above to hand over another way',
        'covering' => '{1} You are invited into the household on :stack, as :name. Open this address to choose your password. It stands for :count hour.|[0,*] You are invited into the household on :stack, as :name. Open this address to choose your password. It stands for :count hours.',
        'joining' => 'On a phone with the lemonfiber app, open this link to join from there:',
        'declining' => 'To turn this invitation down, open this address instead:',
        'would_withdraw' => 'Invitations nobody took up, which this would take back',
        'withdrew' => 'Invitations nobody took up, taken back on the way',
        'nobody_withdrawn' => 'None',
        'would_switch_off' => 'Resets nobody took up, which this would switch off and keep',
        'switched_off' => 'Resets nobody took up, switched off on the way and kept',
        'nobody_switched_off' => 'None',
        'start_again' => 'Start again',
        'who_is_in' => 'Who is in already',
        'ask_who_is_in_again' => 'See who is in again',
        'member' => [
            'joined' => 'In the household',
            'still_invited' => 'Invited, and has not taken it up yet',
        ],
        'nobody_in' => 'Nobody has an account on the media server yet',
        'would_take_it_off' => 'Let :name choose a new password',
        'taking_it_off_means' => 'The password :name has now stops working, and they choose a new one at the address this gives you. You never see or set it',
        'take_it_off' => 'Take the password off for :name',
        'never_mind' => 'Leave it as it is',
        'never_mind_for' => 'Leave the password of :name as it is',
    ],

    // Taking one member out of the household: what it would cost, the yes,
    // and how far it reached.
    'removal' => [
        'would_take_them_out' => 'Take :name out of the household',
        'names_nobody' => 'This names nobody to take out of the household. Choose somebody from who is in',
        'back_to_who_is_in' => 'Back to who is in',
        'taking_out' => 'Taking :name out of the household',
        'reading' => 'Asking this stack what taking :name out would cost',
        'removing' => 'This stack is taking :name out of the household',
        'not_yet' => 'Nobody has been taken out. This is what taking :name out would cost',
        'revoked' => [
            'everywhere' => ':name is out of the household: gone from the media server and from the request service',
            'media-server-only' => ':name can no longer watch or ask, and the request service still holds an account for them. This is not finished: taking them out again removes it',
            'nothing' => 'Nobody has been taken out',
        ],
        'requests_go' => '{0} They have no requests to lose|{1} :count request of theirs goes with them. It is destroyed, not handed to anybody|[2,*] :count requests of theirs go with them. They are destroyed, not handed to anybody',
        'requests_went' => '{0} They had no requests to lose|{1} :count request of theirs went with them. It was destroyed, not handed to anybody|[2,*] :count requests of theirs went with them. They were destroyed, not handed to anybody',
        'asks' => 'They ask for things through the request service, so their account there is taken too',
        'does_not_ask' => 'They have no account on the request service, so there is nothing of theirs to take there',
        'found' => 'What this stack found',
        'found_nothing' => 'Nothing it could not do, and nothing else to tell you',
        'take_them_out' => 'Take :name out of the household',
        'read_again' => 'Ask what taking :name out would cost now',
        'refused' => 'This stack would not take :name out of the household',
        'no_outcome' => 'What taking :name out would cost could not be read: this stack has no outcome for it any more',
        'no_outcome_after_yes' => 'Whether :name was taken out could not be read: this stack has no outcome for it any more. That is not the same as it not having happened',
        'unread_after_yes' => 'Whether :name was taken out could not be read. That is not the same as it not having happened',
    ],
];
