<?php

declare(strict_types=1);

return [

    /*
    | Koji EracuniClient se veže. 'mock' = determinističan lažni klijent (bez ijednog
    | pravog poziva). 'http' = pravi klijent (endpoint + vjerodajnice iz Postavki naplate).
    | Vidi App\Providers\AppServiceProvider.
    */

    'driver' => env('ERACUNI_DRIVER', 'mock'),

    /*
    | Pisanje u eRačune (SalesInvoiceCreate, SalesInvoiceSendByEmail, PartnerCreate).
    | Fiskalizacija je automatska i nepovratna, a nema test organizacije — dok je ovo
    | isključeno, HTTP klijent smije samo ČITATI.
    */

    'allow_write' => (bool) env('ERACUNI_ALLOW_WRITE', false),

    'timeout' => (int) env('ERACUNI_TIMEOUT', 20),

];
