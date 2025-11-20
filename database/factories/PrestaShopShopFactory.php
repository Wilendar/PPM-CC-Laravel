<?php

namespace Database\Factories;

use App\Models\PrestaShopShop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PrestaShopShop model
 *
 * Used in unit tests to generate test shop configurations
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrestaShopShop>
 */
class PrestaShopShopFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PrestaShopShop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Shop',
            'url' => $this->faker->url(),
            'api_key' => $this->faker->uuid(),
            'version' => $this->faker->randomElement(['8', '9']),
            'is_active' => true,
            'sync_enabled' => true,
            'sync_frequency' => 'hourly',
            'last_sync_at' => null,
            'sync_status' => 'pending',
        ];
    }

    /**
     * Indicate that the shop is version 8
     *
     * @return static
     */
    public function version8(): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => '8',
        ]);
    }

    /**
     * Indicate that the shop is version 9
     *
     * @return static
     */
    public function version9(): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => '9',
        ]);
    }

    /**
     * Indicate that the shop is inactive
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'sync_enabled' => false,
        ]);
    }
}
