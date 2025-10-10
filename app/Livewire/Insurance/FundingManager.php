<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\FundingSource;
use App\Models\FundingTransaction;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FundingManager extends Component
{
    public $sources;
    public $transactions;
    public $source_id;
    public $amount;
    public $description;
    public $reference_no;
    public $edit_id;
    public $edit_source_id;
    public $edit_amount;
    public $edit_description;
    public $edit_reference_no;
    public $showEditModal = false;

    // منابع بودجه
    public $source_name;
    public $source_type = 'charity';
    public $source_description;
    public $source_edit_id;
    public $source_edit_name;
    public $source_edit_type;
    public $source_edit_description;
    public $showSourceEditModal = false;

    public $formKey;

    protected $messages = [
        'source_id.required' => 'انتخاب منبع الزامی است.',
        'source_id.exists' => 'منبع انتخابی معتبر نیست.',
        'amount.required' => 'وارد کردن مبلغ الزامی است.',
        'amount.numeric' => 'مبلغ باید عددی باشد.',
        'amount.min' => 'حداقل مبلغ ۱۰۰۰ تومان باید باشد.',
        'description.max' => 'توضیحات نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        'reference_no.max' => 'شماره پیگیری نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        'edit_source_id.required' => 'انتخاب منبع الزامی است.',
        'edit_source_id.exists' => 'منبع انتخابی معتبر نیست.',
        'edit_amount.required' => 'وارد کردن مبلغ الزامی است.',
        'edit_amount.numeric' => 'مبلغ باید عددی باشد.',
        'edit_amount.min' => 'حداقل مبلغ ۱۰۰۰ تومان باید باشد.',
        'edit_description.max' => 'توضیحات نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        'edit_reference_no.max' => 'شماره پیگیری نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        'source_name.required' => 'نام منبع الزامی است.',
        'source_type.required' => 'انتخاب نوع منبع الزامی است.',
        'source_description.max' => 'توضیحات نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        'source_edit_name.required' => 'نام منبع الزامی است.',
        'source_edit_type.required' => 'انتخاب نوع منبع الزامی است.',
        'source_edit_description.max' => 'توضیحات نباید بیشتر از ۲۵۵ کاراکتر باشد.',
    ];

    public function mount()
    {
        $this->formKey = uniqid();
        $this->sources = FundingSource::where('is_active', true)->get();
        $this->transactions = FundingTransaction::with('source')->latest()->take(20)->get();
    }

    private function resetTransactionForm()
    {
        $this->reset(['source_id', 'amount', 'description', 'reference_no']);
    }

    private function resetSourceForm()
    {
        $this->reset(['source_name', 'source_type', 'source_description']);
        $this->source_type = 'charity';
    }

    /**
     * پاک کردن کش بودجه باقی‌مانده
     */
    private function clearBudgetCache()
    {
        Cache::forget('remaining_budget');
        // ارسال event برای به‌روزرسانی navigation
        $this->dispatch('budget-updated');
    }

    public function addTransaction()
    {
        try {
            $this->validate([
                'source_id' => ['required', Rule::exists('funding_sources', 'id')],
                'amount' => 'required|numeric|min:1000',
                'description' => 'nullable|string|max:255',
                'reference_no' => 'nullable|string|max:255',
            ]);
            FundingTransaction::create([
                'funding_source_id' => $this->source_id,
                'amount' => $this->amount,
                'description' => $this->description,
                'reference_no' => $this->reference_no,
            ]);
            
            // پاک کردن کش بودجه پس از ایجاد تراکنش
            $this->clearBudgetCache();
            
            $this->resetTransactionForm();
            $this->formKey = uniqid();
            $this->sources = FundingSource::where('is_active', true)->get();
            $this->transactions = FundingTransaction::with('source')->latest()->take(20)->get();
            session()->flash('success', 'بودجه با موفقیت ثبت شد 🎉');
        } catch (\Throwable $e) {
            session()->flash('error', 'خطایی در ثبت بودجه رخ داد ❌');
        }
    }

    public function deleteTransaction($id)
    {
        try {
            FundingTransaction::findOrFail($id)->delete();
            
            // پاک کردن کش بودجه پس از حذف تراکنش
            $this->clearBudgetCache();
            
            $this->transactions = FundingTransaction::with('source')->latest()->take(20)->get();
            session()->flash('success', 'تراکنش حذف شد.');
        } catch (\Throwable $e) {
            session()->flash('error', 'خطا در حذف تراکنش رخ داد.');
        }
    }

    public function showEditTransaction($id)
    {
        $trx = FundingTransaction::findOrFail($id);
        $this->edit_id = $trx->id;
        $this->edit_source_id = $trx->funding_source_id;
        $this->edit_amount = $trx->amount;
        $this->edit_description = $trx->description;
        $this->edit_reference_no = $trx->reference_no;
        $this->showEditModal = true;
    }

    public function updateTransaction()
    {
        try {
            $this->validate([
                'edit_source_id' => ['required', Rule::exists('funding_sources', 'id')],
                'edit_amount' => 'required|numeric|min:1000',
                'edit_description' => 'nullable|string|max:255',
                'edit_reference_no' => 'nullable|string|max:255',
            ]);
            $trx = FundingTransaction::findOrFail($this->edit_id);
            $trx->update([
                'funding_source_id' => $this->edit_source_id,
                'amount' => $this->edit_amount,
                'description' => $this->edit_description,
                'reference_no' => $this->edit_reference_no,
            ]);
            
            // پاک کردن کش بودجه پس از ویرایش تراکنش
            $this->clearBudgetCache();
            
            $this->showEditModal = false;
            $this->transactions = FundingTransaction::with('source')->latest()->take(20)->get();
            session()->flash('success', 'تراکنش ویرایش شد.');
        } catch (\Throwable $e) {
            session()->flash('error', 'خطا در ویرایش تراکنش رخ داد.');
        }
    }

    // مدیریت منابع بودجه
    public function addSource()
    {
        try {
            $this->validate([
                'source_name' => 'required|string|max:255',
                'source_type' => 'required',
                'source_description' => 'nullable|string|max:255',
            ]);
            FundingSource::create([
                'name' => $this->source_name,
                'type' => $this->source_type,
                'description' => $this->source_description,
                'is_active' => true,
            ]);
            $this->resetSourceForm();
            $this->sources = FundingSource::where('is_active', true)->get();
            session()->flash('success', 'منبع بودجه جدید اضافه شد.');
            $this->dispatch('inputReset');
        } catch (\Throwable $e) {
            session()->flash('error', 'خطا در افزودن منبع بودجه رخ داد.');
        }
    }

    public function showEditSource($id)
    {
        try {
            $src = FundingSource::findOrFail($id);
            $this->source_edit_id = $src->id;
            $this->source_edit_name = $src->name;
            $this->source_edit_type = $src->type;
            $this->source_edit_description = $src->description;
            $this->showSourceEditModal = true;
            
            Log::info('Edit source form opened', [
                'source_id' => $id,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing edit source form', [
                'error' => $e->getMessage(),
                'source_id' => $id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'خطا در نمایش فرم ویرایش منبع رخ داد ❌');
        }
    }

    public function updateSource()
    {
        try {
            $validated = $this->validate([
                'source_edit_name' => 'required|string|max:255',
                'source_edit_type' => 'required|in:charity,bank,insurance,person,government,other',
                'source_edit_description' => 'nullable|string|max:255',
            ]);
            
            $src = FundingSource::findOrFail($this->source_edit_id);
            
            // ثبت لاگ قبل از ویرایش
            Log::info('Updating funding source', [
                'source_id' => $this->source_edit_id,
                'old_data' => [
                    'name' => $src->name,
                    'type' => $src->type,
                    'description' => $src->description,
                ],
                'new_data' => [
                    'name' => $validated['source_edit_name'],
                    'type' => $validated['source_edit_type'],
                    'description' => $validated['source_edit_description'] ?? null,
                ],
                'user_id' => auth()->id(),
            ]);
            
            $src->update([
                'name' => $validated['source_edit_name'],
                'type' => $validated['source_edit_type'],
                'description' => $validated['source_edit_description'] ?? null,
            ]);
            
            // ثبت لاگ بعد از ویرایش موفق
            Log::info('Funding source updated successfully', [
                'source_id' => $this->source_edit_id,
                'user_id' => auth()->id(),
            ]);
            
            $this->showSourceEditModal = false;
            $this->sources = FundingSource::where('is_active', true)->get();
            session()->flash('success', 'منبع بودجه با موفقیت ویرایش شد ✅');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // در صورت خطای validation، پیام خطا را نمایش دهید
            Log::warning('Validation error in updateSource', [
                'errors' => $e->errors(),
                'source_id' => $this->source_edit_id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'لطفا اطلاعات را به درستی وارد کنید ❌');
        } catch (\Throwable $e) {
            Log::error('Error updating funding source', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'source_id' => $this->source_edit_id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'خطا در ویرایش منبع بودجه رخ داد ❌');
        }
    }

    public function deleteSource($id)
    {
        try {
            FundingSource::findOrFail($id)->delete();
            $this->sources = FundingSource::where('is_active', true)->get();
            session()->flash('success', 'منبع بودجه حذف شد.');
        } catch (\Throwable $e) {
            session()->flash('error', 'خطا در حذف منبع بودجه رخ داد.');
        }
    }

    public function render()
    {
        return view('livewire.insurance.funding-manager');
    }
}
