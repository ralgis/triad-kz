<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        // Only useful for tests — production Setting::current() builds the
        // single row from migration defaults.
        return [
            'site_name' => 'ТРИ АД Construction',
            'phone' => '+7 (727) 393-1917',
            'public_email' => 'info@triad.kz',
            'email_recipient' => 'ravacom@mail.ru',
            'address' => 'г. Алматы, ул. Бродского, 186',
            'map_lat' => 43.282317,
            'map_lng' => 76.900101,
        ];
    }
}
