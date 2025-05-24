<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use App\Models\User;
use App\Models\Organization;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class Form extends Component
{
    public $editMode = false;
    public $userId;
    public $first_name;
    public $last_name;
    public $national_code;
    public $mobile;
    public $email;
    public $username;
    public $password;
    public $password_confirmation;
    public $user_type;
    public $organization_id;
    public $role;
    public $organizations = [];
    public $roles = [];

    public function mount($user = null)
    {
        $this->organizations = Organization::all();
        $this->roles = Role::all();
        if ($user) {
            $this->editMode = true;
            $u = User::findOrFail($user);
            $this->userId = $u->id;
            $this->first_name = $u->first_name;
            $this->last_name = $u->last_name;
            $this->national_code = $u->national_code;
            $this->mobile = $u->mobile;
            $this->email = $u->email;
            $this->username = $u->username;
            $this->user_type = $u->user_type;
            $this->organization_id = $u->organization_id;
            $this->role = $u->roles->first()?->name;
        }
    }

    public function rules()
    {
        $uniqueUser = $this->editMode ? ",{$this->userId}" : '';
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_code' => 'nullable|string|max:20',
            'mobile' => 'required|string|max:20|unique:users,mobile' . $uniqueUser,
            'email' => 'nullable|email|max:255|unique:users,email' . $uniqueUser,
            'username' => 'required|string|max:255|unique:users,username' . $uniqueUser,
            'password' => $this->editMode ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
            'user_type' => 'required',
            'organization_id' => 'nullable|exists:organizations,id',
            'role' => 'required|exists:roles,name',
        ];
    }

    public function submit()
    {
        $this->validate();
        if ($this->editMode) {
            $user = User::findOrFail($this->userId);
        } else {
            $user = new User();
        }
        $user->first_name = $this->first_name;
        $user->last_name = $this->last_name;
        $user->national_code = $this->national_code;
        $user->mobile = $this->mobile;
        $user->email = $this->email;
        $user->username = $this->username;
        $user->user_type = $this->user_type;
        $user->organization_id = $this->organization_id;
        if ($this->password) {
            $user->password = Hash::make($this->password);
        }
        $user->save();
        $user->syncRoles([$this->role]);
        session()->flash('success', $this->editMode ? 'کاربر ویرایش شد.' : 'کاربر ایجاد شد.');
        return redirect()->route('admin.users.index');
    }

    public function render()
    {
        return view('livewire.admin.users.form', [
            'organizations' => $this->organizations,
            'roles' => $this->roles,
        ]);
    }
}
