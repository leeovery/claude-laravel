<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\HasTestFactory;

abstract class Data extends \Spatie\LaravelData\Data
{
    use HasTestFactory;
}
