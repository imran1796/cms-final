<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales (field-level localization)
    |--------------------------------------------------------------------------
    | Locale codes allowed for localized entry fields. Used by EntryValidator
    | to validate data.{handle}.{locale} keys. Public API ?locale= projects
    | using LocaleProjector; fallback chain can be added later in delivery.
    */
    'supported_locales' => ['en', 'de'],

    /*
    |--------------------------------------------------------------------------
    | Default locale (field-level localization fallback)
    |--------------------------------------------------------------------------
    | Used when DB content locale settings are unavailable and when the backend
    | needs a deterministic default locale key.
    */
    'default_locale' => 'en',

];
