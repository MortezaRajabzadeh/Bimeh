<?php

namespace App\Http\Livewire\Insurance;

use App\Models\FamilyInsurance;
use App\Models\InsuranceShare;
use App\Models\Organization;
use App\Models\User;
use Livewire\Component;

class ShareManager extends Component
{
    public $familyInsurance;
    public $shares = [];
    public $showAddForm = false;
    
    // فیلدهای فرم
    public $percentage;
    public $payer_type;
    public $payer_name;
    public $payer_organization_id;
    public $payer_user_id;
    public $description;
    
    // متغیرهای کمکی
    public $totalPercentage = 0;
    public $remainingPercentage = 100;
    public $organizations = [];
    public $users = [];
    
    protected $rules = [
        'percentage' => 'required|numeric|min:0.01|max:100',
        'payer_type' => 'required|in:insurance_company,charity,bank,government,individual_donor,csr_budget,other',
        'payer_name' => 'required|string|max:255',
        'payer_organization_id' => 'nullable|exists:organizations,id',
        'payer_user_id' => 'nullable|exists:users,id',
        'description' => 'nullable|string|max:1000',
    ];

    protected $messages = [
        'percentage.required' => 'درصد مشارکت الزامی است.',
        'percentage.numeric' => 'درصد مشارکت باید عدد باشد.',
        'percentage.min' => 'درصد مشارکت باید حداقل ۰.۰۱ باشد.',
        'percentage.max' => 'درصد مشارکت نمی‌تواند بیش از ۱۰۰ باشد.',
        'payer_type.required' => 'نوع پرداخت‌کننده الزامی است.',
        'payer_name.required' => 'نام پرداخت‌کننده الزامی است.',
    ];

    public function mount($familyInsuranceId)
    {
        $this->familyInsurance = FamilyInsurance::with('family')->findOrFail($familyInsuranceId);
        $this->loadShares();
        $this->loadOrganizations();
        $this->loadUsers();
    }

    public function loadShares()
    {
        $this->shares = $this->familyInsurance->shares()
            ->with(['payerOrganization', 'payerUser'])
            ->get()
            ->toArray();
        
        $this->calculateTotals();
    }

    public function loadOrganizations()
    {
        $this->organizations = Organization::active()->get()->toArray();
    }

    public function loadUsers()
    {
        $this->users = User::active()->get()->toArray();
    }

    public function calculateTotals()
    {
        $this->totalPercentage = collect($this->shares)->sum('percentage');
        $this->remainingPercentage = 100 - $this->totalPercentage;
    }

    public function toggleAddForm()
    {
        $this->showAddForm = !$this->showAddForm;
        if (!$this->showAddForm) {
            $this->resetForm();
        }
    }

    public function resetForm()
    {
        $this->percentage = '';
        $this->payer_type = '';
        $this->payer_name = '';
        $this->payer_organization_id = '';
        $this->payer_user_id = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    public function updatedPayerType()
    {
        // پاک کردن فیلدهای مرتبط هنگام تغییر نوع پرداخت‌کننده
        $this->payer_organization_id = '';
        $this->payer_user_id = '';
        $this->payer_name = '';
    }

    public function updatedPayerOrganizationId()
    {
        if ($this->payer_organization_id) {
            $organization = collect($this->organizations)->firstWhere('id', $this->payer_organization_id);
            if ($organization) {
                $this->payer_name = $organization['name'];
            }
        }
    }

    public function updatedPayerUserId()
    {
        if ($this->payer_user_id) {
            $user = collect($this->users)->firstWhere('id', $this->payer_user_id);
            if ($user) {
                $this->payer_name = $user['name'];
            }
        }
    }

    public function addShare()
    {
        // اعتبارسنجی درصد باقیمانده
        if ($this->percentage > $this->remainingPercentage) {
            $this->addError('percentage', "درصد وارد شده بیش از درصد باقیمانده ({$this->remainingPercentage}٪) است.");
            return;
        }

        $this->validate();

        // اعتبارسنجی‌های اضافی بر اساس نوع پرداخت‌کننده
        if (in_array($this->payer_type, ['insurance_company', 'charity', 'bank']) && !$this->payer_organization_id) {
            $this->addError('payer_organization_id', 'انتخاب سازمان برای این نوع پرداخت‌کننده الزامی است.');
            return;
        }

        if ($this->payer_type === 'individual_donor' && !$this->payer_user_id) {
            $this->addError('payer_user_id', 'انتخاب کاربر برای فرد خیر الزامی است.');
            return;
        }

        $share = InsuranceShare::create([
            'family_insurance_id' => $this->familyInsurance->id,
            'percentage' => $this->percentage,
            'payer_type' => $this->payer_type,
            'payer_name' => $this->payer_name,
            'payer_organization_id' => $this->payer_organization_id ?: null,
            'payer_user_id' => $this->payer_user_id ?: null,
            'description' => $this->description,
        ]);

        // محاسبه مبلغ
        $share->calculateAmount();
        $share->save();

        $this->loadShares();
        $this->resetForm();
        $this->showAddForm = false;

        session()->flash('message', 'سهم بیمه با موفقیت اضافه شد.');
    }

    public function deleteShare($shareId)
    {
        $share = InsuranceShare::find($shareId);
        if ($share && $share->family_insurance_id === $this->familyInsurance->id) {
            $share->delete();
            $this->loadShares();
            session()->flash('message', 'سهم بیمه با موفقیت حذف شد.');
        }
    }

    public function markAsPaid($shareId)
    {
        $share = InsuranceShare::find($shareId);
        if ($share && $share->family_insurance_id === $this->familyInsurance->id) {
            $share->update([
                'is_paid' => true,
                'payment_date' => now()->toDateString(),
            ]);
            $this->loadShares();
            session()->flash('message', 'سهم به عنوان پرداخت شده علامت‌گذاری شد.');
        }
    }

    public function getPayerTypes()
    {
        return [
            'insurance_company' => 'شرکت بیمه',
            'charity' => 'خیریه',
            'bank' => 'بانک',
            'government' => 'دولت',
            'individual_donor' => 'فرد خیر',
            'csr_budget' => 'بودجه CSR',
            'other' => 'سایر',
        ];
    }

    public function render()
    {
        return view('livewire.insurance.share-manager', [
            'payerTypes' => $this->getPayerTypes(),
        ]);
    }
} 