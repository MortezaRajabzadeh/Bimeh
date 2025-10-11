<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SavedFilter;
use App\Models\User;

class SavedFilterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SavedFilter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'organization_id' => null,
            'filter_type' => 'families_approval',
            'filters_config' => [],
            'is_active' => true,
            'usage_count' => 0,
            'last_used_at' => null,
        ];
    }

    /**
     * Indicate that the saved filter is for rank settings.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function rankSettings()
    {
        return $this->state(function (array $attributes) {
            return [
                'filter_type' => 'rank_settings',
            ];
        });
    }

    /**
     * Set a specific user for the saved filter.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withUser($user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Set a specific organization for the saved filter.
     *
     * @param mixed $organization
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withOrganization($organization)
    {
        return $this->state(function (array $attributes) use ($organization) {
            return [
                'organization_id' => is_object($organization) ? $organization->id : $organization,
            ];
        });
    }

    /**
     * Set a specific usage count for the saved filter.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withUsageCount($count)
    {
        return $this->state(function (array $attributes) use ($count) {
            return [
                'usage_count' => $count,
            ];
        });
    }

    /**
     * Set a specific configuration for the saved filter.
     *
     * @param array $config
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withConfig($config)
    {
        return $this->state(function (array $attributes) use ($config) {
            return [
                'filters_config' => $config,
            ];
        });
    }

    /**
     * Indicate that the saved filter is inactive.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}