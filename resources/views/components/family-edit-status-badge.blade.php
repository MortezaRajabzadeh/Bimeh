@props(['family', 'user' => null, 'showTooltip' => true, 'size' => 'md'])

@php
    use App\Enums\InsuranceWizardStep;
    use Illuminate\Support\Facades\Gate;
    
    // تعیین کاربر (پیش‌فرض: کاربر احراز هویت شده)
    $user = $user ?? auth()->user();
    
    // بررسی مجوز ویرایش
    $canEdit = Gate::forUser($user)->allows('update', $family);
    
    // دریافت وضعیت wizard
    $wizardStatus = $family->wizard_status;
    
    // دریافت برچسب وضعیت از enum
    $statusLabel = 'نامشخص';
    $wizardStatusValue = null;
    
    if ($wizardStatus) {
        try {
            // بررسی اینکه آیا قبلاً یک enum instance است یا خیر
            if ($wizardStatus instanceof InsuranceWizardStep) {
                $statusEnum = $wizardStatus;
                $wizardStatusValue = $wizardStatus->value;
            } else {
                $statusEnum = InsuranceWizardStep::from($wizardStatus);
                $wizardStatusValue = $wizardStatus;
            }
            $statusLabel = $statusEnum->label();
        } catch (\ValueError $e) {
            $statusLabel = 'وضعیت نامعتبر';
            $wizardStatusValue = is_string($wizardStatus) ? $wizardStatus : null;
        }
    } else {
        $statusLabel = 'در انتظار تایید';
        $wizardStatusValue = null;
    }
    
    // تعریف رنگ‌ها بر اساس wizard_status
    $colorClasses = match($wizardStatusValue) {
        'pending' => 'bg-blue-100 text-blue-800 border-blue-300',
        'reviewing' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'share_allocation' => 'bg-purple-100 text-purple-800 border-purple-300',
        'approved' => 'bg-green-100 text-green-800 border-green-300',
        'excel_upload' => 'bg-teal-100 text-teal-800 border-teal-300',
        'insured' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
        'renewal' => 'bg-cyan-100 text-cyan-800 border-cyan-300',
        'rejected' => 'bg-red-100 text-red-800 border-red-300',
        default => 'bg-gray-100 text-gray-800 border-gray-300',
    };
    
    // اگر کاربر می‌تواند ویرایش کند، رنگ را سبز کن
    if ($canEdit) {
        $colorClasses = 'bg-green-100 text-green-800 border-green-300';
    }
    
    // تعیین متن badge
    $badgeText = $canEdit ? 'قابل ویرایش' : $statusLabel;
    
    // تعیین پیام tooltip
    if ($canEdit) {
        $tooltipMessage = 'شما می‌توانید این خانواده را ویرایش کنید';
    } else {
        if ($user->isCharity()) {
            $tooltipMessage = "این خانواده در مرحله {$statusLabel} است و فقط ادمین می‌تواند ویرایش کند";
        } elseif ($user->isAdmin()) {
            $tooltipMessage = "این خانواده در مرحله {$statusLabel} است";
        } elseif ($user->isInsurance()) {
            $tooltipMessage = "شما دسترسی ویرایش ندارید - وضعیت: {$statusLabel}";
        } else {
            $tooltipMessage = "شما مجوز ویرایش این خانواده را ندارید";
        }
    }
    
    // تعریف کلاس‌های اندازه
    $sizeClasses = match($size) {
        'sm' => 'text-xs px-2 py-0.5',
        'md' => 'text-xs px-2.5 py-1',
        'lg' => 'text-sm px-3 py-1.5',
        default => 'text-xs px-2.5 py-1',
    };
    
    // تعریف اندازه آیکون
    $iconSize = match($size) {
        'sm' => 'w-3 h-3',
        'md' => 'w-3.5 h-3.5',
        'lg' => 'w-4 h-4',
        default => 'w-3.5 h-3.5',
    };
@endphp

<div class="relative group inline-block">
    <span class="inline-flex items-center rounded-full font-medium border-2 transition-all duration-200 cursor-help hover:scale-105 {{ $colorClasses }} {{ $sizeClasses }}">
        {{-- آیکون --}}
        @if($canEdit)
            {{-- آیکون مداد برای قابل ویرایش --}}
            <svg class="{{ $iconSize }} ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        @else
            {{-- آیکون قفل برای غیرقابل ویرایش --}}
            <svg class="{{ $iconSize }} ml-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" 
                      d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" 
                      clip-rule="evenodd"/>
            </svg>
        @endif
        
        {{-- متن badge --}}
        <span>{{ $badgeText }}</span>
    </span>
    
    {{-- Tooltip --}}
    @if($showTooltip)
        <div class="tooltip-edit-status absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 
                    transition-all duration-300 pointer-events-none z-50 group-hover:opacity-100 group-hover:pointer-events-auto">
            <div class="bg-gray-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap max-w-xs shadow-lg">
                <div class="font-semibold mb-1">
                    @if($canEdit)
                        ✓ قابل ویرایش
                    @else
                        🔒 غیرقابل ویرایش
                    @endif
                </div>
                <div>{{ $tooltipMessage }}</div>
            </div>
            {{-- فلش tooltip --}}
            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 
                        border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
        </div>
    @endif
</div>

{{-- استایل‌های CSS --}}
<style>
.tooltip-edit-status {
    animation: tooltipEditStatusFadeIn 0.3s ease-out;
}

@keyframes tooltipEditStatusFadeIn {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

/* افکت hover برای badge */
.group:hover span.inline-flex {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* رسپانسیو برای موبایل */
@media (max-width: 640px) {
    .tooltip-edit-status {
        display: none; /* مخفی کردن tooltip در موبایل */
    }
    
    .group span.inline-flex {
        font-size: 10px;
        padding: 2px 6px;
    }
    
    .group span.inline-flex svg {
        width: 10px;
        height: 10px;
    }
}
</style>
