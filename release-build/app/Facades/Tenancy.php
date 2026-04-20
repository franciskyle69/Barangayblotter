<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Tenant|null current()
 * @method static string|null getId()
 * @method static mixed get(string $key, $default = null)
 * @method static array all()
 *
 * @see \App\Services\TenancyManager
 */
class Tenancy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tenancy_manager';
    }
}
