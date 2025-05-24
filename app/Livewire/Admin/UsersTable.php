<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UsersTable extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        session()->flash('success', 'کاربر با موفقیت حذف شد.');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                  ->orWhere('last_name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('mobile', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%");
            })
            ->with(['organization', 'roles'])
            ->latest()
            ->paginate(15);

        return view('livewire.admin.users-table', [
            'users' => $users,
        ]);
    }
}
