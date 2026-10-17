<?php

declare(strict_types=1);

return [

    /*
    | Feature flagovi. S isključenim flagom pripadajući modul je potpuno inertan —
    | nema ruta, navigacije, ekrana ni promjene ponašanja (aplikacija izgleda i radi
    | točno kao danas). Modul "Članarine" cijeli stoji iza 'payments'.
    */

    'payments' => (bool) env('FEATURE_PAYMENTS', false),

];
