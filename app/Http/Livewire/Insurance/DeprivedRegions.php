<?php

namespace App\Http\Livewire\Insurance;

use Livewire\Component;
use App\Models\Province;
use App\Models\City;
use App\Models\District;

class DeprivedRegions extends Component
{
    public $search = '';

    public function render()
    {
        $provinces = Province::with(['cities.districts' => function($q) {
            $q->orderBy('name');
        }, 'cities' => function($q) {
            $q->orderBy('name');
        }])->orderBy('name')
            ->get();

        $search = trim($this->search);
        if ($search) {
            $provinces = $provinces->filter(function($province) use ($search) {
                $found = false;
                if (str_contains($province->name, $search)) {
                    $found = true;
                }
                foreach ($province->cities as $city) {
                    if (str_contains($city->name, $search)) {
                        $found = true;
                    }
                    foreach ($city->districts as $district) {
                        if (str_contains($district->name, $search)) {
                            $found = true;
                        }
                    }
                }
                return $found;
            });
        }

        return view('insurance.deprived-regions', [
            'provinces' => $provinces,
            'search' => $this->search,
        ]);
    }
} 