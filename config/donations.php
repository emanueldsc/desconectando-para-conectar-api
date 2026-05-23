<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Donation Edit Window
    |--------------------------------------------------------------------------
    |
    | Número de minutos após o registro de uma doação durante os quais ela
    | pode ser editada ou excluída. Use 0 para não ter limite de tempo.
    |
    */
    'edit_window_minutes' => (int) env('DONATION_EDIT_WINDOW_MINUTES', 60),
];
