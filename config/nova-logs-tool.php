<?php

return [
    'perPage' => env('NOVA_LOGS_PER_PAGE', 6),
    'regexForFiles' => env('NOVA_LOGS_REGEX_FOR_FILES', '/^laravel/'),
    'filesOrder' => env('NOVA_LOGS_ORDER', 'last_modified'), // Allowed: last_modified, name_asc, name_desc
];
