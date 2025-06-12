<?php

namespace App\Http\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserManager extends Component
{
    use WithPagination;

    public $form = [];
    public $showModal = false;
    public $confirmingDelete = false;
    public $deleteId = null;

    protected function rules()
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'form.email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'form.password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
        ];
    }

    protected $validationAttributes = [
        'form.name' => 'نام',
        'form.username' => 'نام کاربری',
        'form.email' => 'ایمیل',
        'form.password' => 'رمز عبور',
        'form.password_confirmation' => 'تکرار رمز عبور',
    ];

    public function render()
    {
        $users = User::orderByDesc('created_at')->paginate(10);
        return view('livewire.admin.users.user-manager', compact('users'));
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->form = [];
        $this->resetValidation();
    }

    public function createUser()
    {
        $this->validate();

        User::create([
            'name' => $this->form['name'],
            'username' => $this->form['username'],
            'email' => $this->form['email'],
            'password' => Hash::make($this->form['password']),
        ]);

        session()->flash('success', 'کاربر با موفقیت ایجاد شد.');
        $this->closeModal();
        $this->resetForm();
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->confirmingDelete = true;
    }

    public function deleteUser()
    {
        User::findOrFail($this->deleteId)->delete();
        session()->flash('success', 'کاربر با موفقیت حذف شد.');
        $this->confirmingDelete = false;
        $this->deleteId = null;
        $this->resetPage();
    }
} 
