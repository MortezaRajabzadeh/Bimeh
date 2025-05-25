<?php
namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\InsuranceAllocation;
use App\Models\Family;
use App\Models\FundingTransaction;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use \Livewire\WithPagination;

class PaidClaims extends Component
{
    use WithPagination;

    public $addMode = false;
    public $editId = null;
    public $amount;
    public $funding_transaction_id;
    public $families = [];
    public $transactions = [];
    public $issue_date;
    public $paid_at;
    public $description;
    public $family_id;
    public $selectedFamily;
    public $selectedTransaction;
    public $perPage = 10;
    protected $queryString = ['perPage'];

    protected $rules = [
        'family_id' => 'nullable|exists:families,id',
        'amount' => 'required|numeric|min:1',
        'funding_transaction_id' => 'nullable|exists:funding_transactions,id',
        'issue_date' => 'nullable|string|max:20',
        'paid_at' => 'nullable|string|max:20',
        'description' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'family_id.exists' => 'خانواده انتخابی معتبر نیست.',
        'amount.required' => 'مبلغ الزامی است.',
        'amount.numeric' => 'مبلغ باید عددی باشد.',
        'amount.min' => 'مبلغ باید بیشتر از صفر باشد.',
        'funding_transaction_id.exists' => 'تراکنش انتخابی معتبر نیست.',
    ];

    public function mount()
    {
        $this->families = collect(Family::all());
        $this->transactions = collect(FundingTransaction::all());
        $this->selectedFamily = null;
        $this->selectedTransaction = null;
    }

    public function addClaim()
    {
        $this->validate();
        $claim = InsuranceAllocation::create([
            'family_id' => $this->family_id,
            'amount' => $this->amount,
            'funding_transaction_id' => $this->funding_transaction_id,
            'issue_date' => $this->issue_date,
            'paid_at' => $this->paid_at,
            'description' => $this->description,
        ]);
        $this->resetForm();
        $this->resetPage();
        $this->addMode = false;
        session()->flash('success', 'خسارت با موفقیت ثبت شد.');
    }

    public function deleteClaim($id)
    {
        InsuranceAllocation::findOrFail($id)->delete();
        $this->resetPage();
        session()->flash('success', 'خسارت حذف شد.');
    }

    public function showAddForm()
    {
        $this->addMode = true;
        $this->resetForm();
    }

    public function cancelAdd()
    {
        $this->addMode = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->family_id = null;
        $this->selectedFamily = null;
        $this->amount = null;
        $this->funding_transaction_id = null;
        $this->selectedTransaction = null;
        $this->issue_date = null;
        $this->paid_at = null;
        $this->description = null;
    }

    public function updatedFamilyId($value)
    {
        $found = $this->families->firstWhere('id', $value);
        $this->selectedFamily = is_object($found) ? $found : ($found ? (object)json_decode(json_encode($found), false) : null);
    }

    public function updatedFundingTransactionId($value)
    {
        $found = $this->transactions->firstWhere('id', $value);
        $this->selectedTransaction = is_object($found) ? $found : ($found ? (object)json_decode(json_encode($found), false) : null);
    }

    public function editClaim($id)
    {
        $claim = InsuranceAllocation::findOrFail($id);
        $this->editId = $claim->id;
        $this->family_id = $claim->family_id;
        $this->amount = $claim->amount;
        $this->funding_transaction_id = $claim->funding_transaction_id;
        $this->issue_date = $claim->issue_date;
        $this->paid_at = $claim->paid_at;
        $this->description = $claim->description;
        $this->addMode = false;
        $foundFamily = $this->families->firstWhere('id', $claim->family_id);
        $this->selectedFamily = is_object($foundFamily) ? $foundFamily : ($foundFamily ? (object)json_decode(json_encode($foundFamily), false) : null);
        $foundTx = $this->transactions->firstWhere('id', $claim->funding_transaction_id);
        $this->selectedTransaction = is_object($foundTx) ? $foundTx : ($foundTx ? (object)json_decode(json_encode($foundTx), false) : null);
    }

    public function updateClaim()
    {
        $this->validate();
        $claim = InsuranceAllocation::findOrFail($this->editId);
        $claim->update([
            'family_id' => $this->family_id,
            'amount' => $this->amount,
            'funding_transaction_id' => $this->funding_transaction_id,
            'issue_date' => $this->issue_date,
            'paid_at' => $this->paid_at,
            'description' => $this->description,
        ]);
        $this->resetForm();
        $this->editId = null;
        $this->selectedFamily = null;
        $this->selectedTransaction = null;
        $this->resetPage();
        session()->flash('success', 'خسارت با موفقیت ویرایش شد.');
    }

    public function cancelEdit()
    {
        $this->editId = null;
        $this->resetForm();
    }

    public function getClaimsProperty()
    {
        return InsuranceAllocation::with(['family', 'transaction'])->latest()->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.insurance.paid-claims', [
            'addMode' => $this->addMode,
            'families' => $this->families,
            'transactions' => $this->transactions,
            'claims' => $this->getClaimsProperty(),
        ]);
    }
} 