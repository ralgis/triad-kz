<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Inquiry email
    |---------------------------------------------------------------------------
    |
    | Fallback email address for the "куда падают заявки" recipient when
    | the Setting::current()->email_recipient row is empty. In practice the
    | admin sets this in the Settings page once and forgets it; this env
    | value just keeps the system functional on a fresh install.
    |
    */
    'inquiry_email' => env('TRIAD_INQUIRY_EMAIL', 'ravacom@mail.ru'),

    /*
    |---------------------------------------------------------------------------
    | Settings cache TTL
    |---------------------------------------------------------------------------
    |
    | How long header/footer partials cache the Setting::current() row.
    | Defaults to 1 hour. Set 0 to disable caching during development.
    |
    */
    'settings_cache_ttl' => env('TRIAD_SETTINGS_CACHE_TTL', 3600),

];
