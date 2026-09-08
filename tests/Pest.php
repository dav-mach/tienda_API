<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| En este proyecto los tests están escritos como clases PHPUnit
| tradicionales (cada una extends Tests\TestCase y declara sus propios
| traits). Pest los ejecuta igual, sin necesidad de configuración extra
| acá. Se deja este binding por compatibilidad.
*/

pest()->extend(TestCase::class)->in('Feature', 'Unit');
