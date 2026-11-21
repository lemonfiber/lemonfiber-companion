<?php

declare(strict_types=1);

return [
    'title' => 'Serving and certificate',
    'section' => 'Served',
    'named' => 'Answers to',
    'no_name' => 'Any name that reaches it',
    'port' => 'Port',
    'encrypted' => [
        'heading' => 'Encrypted',
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'network' => [
        'heading' => 'Offered to',
        'yes' => 'The network',
        'no' => 'This machine only',
    ],
    'unrecorded' => 'Where it is served could not be written down:',
    'certificate' => 'Certificate',
    'pin' => [
        'presented' => 'It presents',
        'held' => 'This phone pins',
        'same' => 'The same: this phone recognises it.',
        'different' => 'Different: this phone would refuse it. Pair this phone again to trust it.',
    ],
    'subject' => 'Made out to',
    'issuer' => 'Signed by',
    'self_signed' => 'Itself',
    'cut' => 'Cut short by the stack.',
    'valid' => [
        'from' => 'Valid since',
        'from_ahead' => 'Valid from',
        'until' => 'Valid until',
        'lapsed' => 'Expired',
    ],
    'names' => 'Names it covers',
    'no_names' => 'No names',
    'names_more' => '{1} And one more name|[2,*] And :count more names',
    'plain' => 'It is served without encryption, so it presents no certificate.',
    'replacing' => 'The certificate is replaced at the machine, and every paired phone then pairs again.',
];
