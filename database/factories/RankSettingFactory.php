<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\RankSetting;

class RankSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RankSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->randomElement([
            'سرپرست خانوار زن',
            'خانواده کم برخوردار',
            'بیماری خاص',
            'اعتیاد',
            'بیکاری',
            'معلولیت',
            'از کار افتادگی',
            'کهولت سن',
            'رها شده',
            'خانواده مهاجر'
        ]);

        return [
            'name' => $name,
            'key' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'weight' => $this->faker->numberBetween(0, 10),
            'category' => $this->faker->randomElement(['disability', 'disease', 'addiction', 'economic', 'social', 'other']),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'requires_document' => $this->faker->boolean(),
        ];
    }

    /**
     * Indicate that the rank setting is inactive.
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

    /**
     * Indicate that the rank setting requires document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withDocument()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_document' => true,
            ];
        });
    }

    /**
     * Indicate that the rank setting does not require document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withoutDocument()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_document' => false,
            ];
        });
    }

    /**
     * Set a specific weight for the rank setting.
     *
     * @param int $weight
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withWeight($weight)
    {
        return $this->state(function (array $attributes) use ($weight) {
            return [
                'weight' => $weight,
            ];
        });
    }
}