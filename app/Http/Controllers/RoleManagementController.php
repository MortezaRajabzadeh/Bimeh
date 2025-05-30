<?php

namespace App\Http\Controllers;

use App\Models\CustomRole;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RoleManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = CustomRole::with(['parent', 'children', 'permissions'])
            ->orderBy('name')
            ->get();

        // ساخت درخت سلسله‌مراتبی
        $roleTree = $this->buildTree($roles);

        return view('admin.access-levels.index', compact('roles', 'roleTree'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::all();
        $roles = CustomRole::whereNull('parent_id')->get();
        
        return view('admin.access-levels.create', compact('permissions', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:roles,id',
            'inherit_permissions' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ], [
            'name.required' => 'وارد کردن نام نقش الزامی است.',
            'name.string' => 'نام نقش باید متن باشد.',
            'name.max' => 'نام نقش نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'name.unique' => 'این نام نقش قبلاً ثبت شده است.',
            'display_name.required' => 'وارد کردن نام نمایشی الزامی است.',
            'display_name.string' => 'نام نمایشی باید متن باشد.',
            'display_name.max' => 'نام نمایشی نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'description.string' => 'توضیحات باید متن باشد.',
            'parent_id.exists' => 'نقش والد انتخاب شده معتبر نیست.',
            'permissions.array' => 'مجوزها باید آرایه باشد.',
            'permissions.*.exists' => 'یکی از مجوزهای انتخاب شده معتبر نیست.',
        ]);

        // بررسی عدم ایجاد حلقه در سلسله‌مراتب
        if ($validated['parent_id']) {
            $parent = CustomRole::find($validated['parent_id']);
            if ($this->wouldCreateCycle($parent, null)) {
                return back()->withErrors(['parent_id' => 'انتخاب این والد باعث ایجاد حلقه در سلسله‌مراتب می‌شود.']);
            }
        }

        $role = CustomRole::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'inherit_permissions' => $validated['inherit_permissions'] ?? true,
            'guard_name' => 'web',
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.access-levels.index')
            ->with('success', 'نقش جدید با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomRole $accessLevel)
    {
        $accessLevel->load(['permissions', 'parent', 'children']);
        $inheritedPermissions = $accessLevel->getInheritedPermissions();
        
        return view('admin.access-levels.show', compact('accessLevel', 'inheritedPermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomRole $accessLevel)
    {
        $permissions = Permission::all();
        $availableParents = CustomRole::where('id', '!=', $accessLevel->id)
            ->whereNotIn('id', $accessLevel->descendants()->pluck('id'))
            ->get();
        
        return view('admin.access-levels.edit', compact('accessLevel', 'permissions', 'availableParents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomRole $accessLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $accessLevel->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:roles,id',
            'inherit_permissions' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ], [
            'name.required' => 'وارد کردن نام نقش الزامی است.',
            'name.string' => 'نام نقش باید متن باشد.',
            'name.max' => 'نام نقش نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'name.unique' => 'این نام نقش قبلاً ثبت شده است.',
            'display_name.required' => 'وارد کردن نام نمایشی الزامی است.',
            'display_name.string' => 'نام نمایشی باید متن باشد.',
            'display_name.max' => 'نام نمایشی نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'description.string' => 'توضیحات باید متن باشد.',
            'parent_id.exists' => 'نقش والد انتخاب شده معتبر نیست.',
            'permissions.array' => 'مجوزها باید آرایه باشد.',
            'permissions.*.exists' => 'یکی از مجوزهای انتخاب شده معتبر نیست.',
        ]);

        // بررسی عدم ایجاد حلقه در سلسله‌مراتب
        if ($validated['parent_id'] && $validated['parent_id'] != $accessLevel->parent_id) {
            $parent = CustomRole::find($validated['parent_id']);
            if ($this->wouldCreateCycle($parent, $accessLevel)) {
                return back()->withErrors(['parent_id' => 'انتخاب این والد باعث ایجاد حلقه در سلسله‌مراتب می‌شود.']);
            }
        }

        $accessLevel->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'inherit_permissions' => $validated['inherit_permissions'] ?? true,
        ]);

        if (isset($validated['permissions'])) {
            $accessLevel->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.access-levels.index')
            ->with('success', 'نقش با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomRole $accessLevel)
    {
        // بررسی اینکه آیا این نقش دارای فرزند است
        if ($accessLevel->children()->count() > 0) {
            return back()->withErrors(['delete' => 'این نقش دارای زیرمجموعه است و نمی‌تواند حذف شود.']);
        }

        // بررسی اینکه آیا کاربری از این نقش استفاده می‌کند
        if ($accessLevel->users()->count() > 0) {
            return back()->withErrors(['delete' => 'این نقش به کاربران تخصیص داده شده و نمی‌تواند حذف شود.']);
        }

        $accessLevel->delete();

        return redirect()->route('admin.access-levels.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    /**
     * ساخت درخت سلسله‌مراتبی
     */
    private function buildTree($roles, $parentId = null)
    {
        $tree = [];
        
        foreach ($roles as $role) {
            if ($role->parent_id == $parentId) {
                $children = $this->buildTree($roles, $role->id);
                if ($children) {
                    // تبدیل array به Collection تا با روش‌های Eloquent سازگار باشد
                    $role->setRelation('children', collect($children));
                } else {
                    $role->setRelation('children', collect([]));
                }
                $tree[] = $role;
            }
        }
        
        return $tree;
    }

    /**
     * بررسی اینکه آیا تنظیم والد جدید باعث ایجاد حلقه می‌شود
     */
    private function wouldCreateCycle($parent, $role)
    {
        if (!$parent || !$role) {
            return false;
        }

        $current = $parent;
        while ($current) {
            if ($current->id === $role->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * گرفتن IDهای تمام فرزندان یک نقش
     */
    private function getDescendantIds($role)
    {
        $ids = [];
        $children = $role->children;
        
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        
        return $ids;
    }
}
