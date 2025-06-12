<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AccessLevelController extends Controller
{
    /**
     * سطوح دسترسی مجاز در سیستم
     */
    protected $allowedRoles = ['admin', 'charity', 'insurance'];
    
    /**
     * نمایش لیست سطوح دسترسی
     */
    public function index(Request $request)
    {
        $roles = Role::query()->whereIn('name', $this->allowedRoles)->paginate(10);
        $permissions = Permission::all();
        
        return view('admin.access-levels.index', compact('roles', 'permissions'));
    }

    /**
     * نمایش فرم ایجاد سطح دسترسی (غیرفعال)
     */
    public function create()
    {
        // ایجاد سطح دسترسی جدید مجاز نیست
        $permissions = Permission::all();
        return view('admin.access-levels.create', compact('permissions'));
    }

    /**
     * ذخیره سطح دسترسی جدید (غیرفعال)
     */
    public function store(Request $request)
    {
        // ایجاد سطح دسترسی جدید مجاز نیست
        return redirect()->route('admin.access-levels.index')
            ->with('error', 'امکان ایجاد سطح دسترسی جدید وجود ندارد');
    }

    /**
     * نمایش فرم ویرایش سطح دسترسی
     */
    public function edit(Role $accessLevel)
    {
        // فقط سطوح دسترسی مجاز قابل ویرایش هستند
        if (!in_array($accessLevel->name, $this->allowedRoles)) {
            return redirect()->route('admin.access-levels.index')
                ->with('error', 'این سطح دسترسی قابل ویرایش نیست');
        }
        
        $permissions = Permission::all();
        return view('admin.access-levels.edit', compact('accessLevel', 'permissions'));
    }

    /**
     * بروزرسانی سطح دسترسی
     */
    public function update(Request $request, Role $accessLevel)
    {
        // فقط سطوح دسترسی مجاز قابل بروزرسانی هستند
        if (!in_array($accessLevel->name, $this->allowedRoles)) {
            return redirect()->route('admin.access-levels.index')
                ->with('error', 'این سطح دسترسی قابل بروزرسانی نیست');
        }
        
        // نام سطح دسترسی قابل تغییر نیست
        if ($request->input('name') !== $accessLevel->name) {
            return redirect()->route('admin.access-levels.edit', $accessLevel)
                ->with('error', 'نام سطح دسترسی قابل تغییر نیست');
        }
        
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $permissions = isset($validated['permissions']) 
            ? Permission::whereIn('id', $validated['permissions'])->get() 
            : [];
            
        $accessLevel->syncPermissions($permissions);
        
        return redirect()->route('admin.access-levels.index')
            ->with('success', 'مجوزهای سطح دسترسی با موفقیت بروزرسانی شد');
    }

    /**
     * حذف سطح دسترسی (غیرفعال)
     */
    public function destroy(Role $accessLevel)
    {
        // حذف سطوح دسترسی مجاز نیست
        return redirect()->route('admin.access-levels.index')
            ->with('error', 'امکان حذف سطوح دسترسی وجود ندارد');
    }
} 
