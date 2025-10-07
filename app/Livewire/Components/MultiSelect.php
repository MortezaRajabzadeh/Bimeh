<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Modelable;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MultiSelect extends Component
{
    public array $options = [];
    #[Modelable]
    public array $selected = [];
    public string $placeholder = 'انتخاب کنید...';
    public bool $searchable = true;
    public string $name = '';
    public ?string $error = null;
    public bool $required = false;
    public int $maxSelections = 0;

    protected $listeners = ['clearMultiSelect', 'setMultiSelectOptions'];

    public function mount(
        array $options = [],
        array $selected = [],
        string $placeholder = 'انتخاب کنید...',
        bool $searchable = true,
        string $name = '',
        bool $required = false,
        int $maxSelections = 0
    ) {
        try {
            $this->options = $options;
            $this->selected = is_array($selected) ? $selected : [];
            $this->placeholder = $placeholder;
            $this->searchable = $searchable;
            $this->name = $name;
            $this->required = $required;
            $this->maxSelections = $maxSelections;

            // Validate that selected items exist in options
            $this->selected = array_filter($this->selected, fn($key) => isset($this->options[$key]));
            $this->selected = array_values(array_unique($this->selected));

            Log::info('MultiSelect component initialized', [
                'name' => $this->name,
                'options_count' => count($this->options),
                'selected_count' => count($this->selected)
            ]);
        } catch (\Exception $e) {
            Log::error('Error in mount: ' . $e->getMessage());
            $this->error = 'خطا در مقداردهی اولیه کامپوننت';
        }
    }

    public function toggle(string $key)
    {
        try {
            if ($this->maxSelections > 0 && count($this->selected) >= $this->maxSelections && !in_array($key, $this->selected)) {
                $this->dispatch('toast', [
                    'message' => "حداکثر {$this->maxSelections} مورد قابل انتخاب است",
                    'type' => 'warning'
                ]);
                return;
            }

            if (in_array($key, $this->selected)) {
                $this->selected = array_values(array_diff($this->selected, [$key]));
            } else {
                $this->selected[] = $key;
                $this->selected = array_unique($this->selected);
            }

            $this->updatedSelected();
        } catch (\Exception $e) {
            Log::error('Error in toggle: ' . $e->getMessage());
            $this->error = 'خطا در تغییر انتخاب';
        }
    }

    public function clearAll()
    {
        try {
            $this->selected = [];
            $this->dispatch('cleared', ['name' => $this->name]);
            $this->updatedSelected();
        } catch (\Exception $e) {
            Log::error('Error in clearAll: ' . $e->getMessage());
            $this->error = 'خطا در پاک کردن انتخاب‌ها';
        }
    }

    public function updatedSelected()
    {
        try {
            $this->selected = array_values(array_unique($this->selected));
            $this->selected = array_filter($this->selected, fn($key) => isset($this->options[$key]));

            $this->dispatch('selectionChanged', [
                'name' => $this->name,
                'selected' => $this->selected,
                'count' => count($this->selected)
            ]);

            $this->dispatch('input', $this->selected);

            Log::info('Selection changed', [
                'name' => $this->name,
                'selected' => $this->selected
            ]);
        } catch (\Exception $e) {
            Log::error('Error in updatedSelected: ' . $e->getMessage());
        }
    }

    public function validate()
    {
        try {
            $this->error = null;

            if ($this->required && empty($this->selected)) {
                $this->error = 'انتخاب حداقل یک مورد الزامی است';
                return false;
            }

            if ($this->maxSelections > 0 && count($this->selected) > $this->maxSelections) {
                $this->error = "حداکثر {$this->maxSelections} مورد قابل انتخاب است";
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error in validate: ' . $e->getMessage());
            $this->error = 'خطا در اعتبارسنجی';
            return false;
        }
    }

    public function getSelectedCountProperty()
    {
        return count($this->selected);
    }

    public function clearMultiSelect($name)
    {
        if ($name === $this->name) {
            $this->clearAll();
        }
    }

    public function setMultiSelectOptions($name, $options)
    {
        if ($name === $this->name) {
            $this->options = $options;
            $this->selected = array_filter($this->selected, fn($key) => isset($this->options[$key]));
        }
    }

    public function render()
    {
        return view('livewire.components.multi-select');
    }
}
