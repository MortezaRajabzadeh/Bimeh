<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;

class Index extends Component
{
    public $search = '';

    public function updatedSearch()
    {
        // فقط کافی است Livewire را رفرش کند
    }

    public function deleteUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->delete();
        session()->flash('success', 'کاربر با موفقیت حذف شد.');
    }

    public function render()
    {
        $users = \App\Models\User::query()
            ->when($this->search, function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                  ->orWhere('last_name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('mobile', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(15);

        return view('admin.users.index', [
            'users' => $users,
            'organizations' => \App\Models\Organization::active()->get(),
            'roles' => \Spatie\Permission\Models\Role::all(),
        ]);
    }
}
