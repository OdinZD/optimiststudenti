<?php

declare(strict_types=1);

return [

    /*
    | Koji EracuniClient se veže. 'mock' = determinističan lažni klijent (bez ijednog
    | pravog poziva). 'http' = pravi klijent (još nije konfiguriran — dolazi kad stigne
    | API pristup + testna okolina). Vidi App\Providers\AppServiceProvider.
    */

    'driver' => env('ERACUNI_DRIVER', 'mock'),

];
