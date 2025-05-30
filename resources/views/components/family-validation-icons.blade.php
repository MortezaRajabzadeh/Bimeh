@props(['family', 'showTooltips' => true, 'size' => 'md'])

@php
    $validationData = $family->getAllValidationStatuses();
    $iconConfig = config('ui.family_validation_icons');
    $colorConfig = config('ui.status_colors');
    
    // تعیین اندازه آیکون‌ها برای حالت stacked
    $sizeClasses = [
        'sm' => 'w-5 h-5',
        'md' => 'w-6 h-6', 
        'lg' => 'w-8 h-8'
    ];
    $iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div class="flex flex-col items-center justify-center gap-1 family-validation-icons-stacked">
    {{-- آیکون اطلاعات هویتی --}}
    @php
        $identityStatus = $validationData['identity'];
        $identityColors = $colorConfig[$identityStatus['status']] ?? $colorConfig['unknown'] ?? [
            'bg_class' => 'bg-gray-100', 
            'border_class' => 'border-gray-300', 
            'icon_class' => 'text-gray-600', 
            'text_class' => 'text-gray-800'
        ];
    @endphp
    
    <div class="relative group validation-icon-wrapper">
        <div class="validation-icon {{ $iconSize }} {{ $identityColors['bg_class'] }} {{ $identityColors['border_class'] }} 
                    border-2 rounded-lg flex items-center justify-center cursor-help transition-all duration-200 hover:scale-110 hover:z-30">
            {{-- آیکون کاربر --}}
            <svg class="w-4 h-4 {{ $identityColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            
            {{-- نمایش درصد --}}
            @if($identityStatus['status'] !== 'complete' && $identityStatus['status'] !== 'unknown')
                <span class="absolute -bottom-1 -right-1 bg-white text-xs font-bold rounded-full w-4 h-4 flex items-center justify-center border text-[10px] {{ $identityColors['text_class'] }}">
                    {{ $identityStatus['percentage'] }}
                </span>
            @endif
        </div>
        
        {{-- Tooltip --}}
        @if($showTooltips)
            <div class="tooltip-content absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 
                        transition-all duration-300 pointer-events-none z-50 group-hover:opacity-100 group-hover:pointer-events-auto">
                <div class="bg-gray-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap max-w-xs">
                    <div class="font-semibold">{{ $iconConfig['identity']['title'] }}</div>
                    <div class="text-xs mt-1">{{ $identityStatus['message'] }}</div>
                    @if(!empty($identityStatus['details']))
                        <div class="text-xs mt-1 border-t border-gray-600 pt-1">
                            {{ $identityStatus['complete_members'] }}/{{ $identityStatus['total_members'] }} عضو کامل
                        </div>
                    @endif
                </div>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
            </div>
        @endif
    </div>
    
    {{-- آیکون موقعیت جغرافیایی --}}
    @php
        $locationStatus = $validationData['location'];
        $locationColors = $colorConfig[$locationStatus['status']] ?? $colorConfig['unknown'] ?? [
            'bg_class' => 'bg-gray-100', 
            'border_class' => 'border-gray-300', 
            'icon_class' => 'text-gray-600', 
            'text_class' => 'text-gray-800'
        ];
    @endphp
    
    <div class="relative group validation-icon-wrapper">
        <div class="validation-icon {{ $iconSize }} {{ $locationColors['bg_class'] }} {{ $locationColors['border_class'] }} 
                    border-2 rounded-lg flex items-center justify-center cursor-help transition-all duration-200 hover:scale-110 hover:z-30">
            {{-- آیکون مکان --}}
            <svg class="w-4 h-4 {{ $locationColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            
            {{-- نشانه محرومیت --}}
            @if($locationStatus['is_deprived'] === true)
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-3 h-3 flex items-center justify-center text-[8px]">
                    !
                </span>
            @endif
        </div>
        
        {{-- Tooltip --}}
        @if($showTooltips)
            <div class="tooltip-content absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 
                        transition-all duration-300 pointer-events-none z-50 group-hover:opacity-100 group-hover:pointer-events-auto">
                <div class="bg-gray-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap max-w-xs">
                    <div class="font-semibold">{{ $iconConfig['location']['title'] }}</div>
                    <div class="text-xs mt-1">{{ $locationStatus['message'] }}</div>
                    @if($locationStatus['province_name'])
                        <div class="text-xs mt-1 border-t border-gray-600 pt-1">
                            استان: {{ $locationStatus['province_name'] }}
                            @if($locationStatus['deprivation_rank'])
                                <br>رتبه: {{ $locationStatus['deprivation_rank'] }}
                            @endif
                        </div>
                    @endif
                </div>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
            </div>
        @endif
    </div>
    
    {{-- آیکون اطلاعات ناقص اعضا --}}
    @php
        $incompleteMembers = $family->members->where('has_incomplete_data', true)->count();
        $totalMembers = $family->members->count();
        $incompletePercentage = $totalMembers > 0 ? round(($incompleteMembers / $totalMembers) * 100) : 0;
        
        if ($incompleteMembers > 0) {
            $incompleteStatus = 'warning';
            $incompleteMessage = "{$incompleteMembers} عضو دارای اطلاعات ناقص";
        } else {
            $incompleteStatus = 'complete';
            $incompleteMessage = "اطلاعات همه اعضا کامل است";
        }
        
        $incompleteColors = $colorConfig[$incompleteStatus] ?? $colorConfig['unknown'] ?? [
            'bg_class' => 'bg-gray-100', 
            'border_class' => 'border-gray-300', 
            'icon_class' => 'text-gray-600', 
            'text_class' => 'text-gray-800'
        ];
    @endphp
    
    <div class="relative group validation-icon-wrapper">
        <div class="validation-icon {{ $iconSize }} {{ $incompleteColors['bg_class'] }} {{ $incompleteColors['border_class'] }} 
                    border-2 rounded-lg flex items-center justify-center cursor-help transition-all duration-200 hover:scale-110 hover:z-30">
            {{-- آیکون اطلاعات ناقص --}}
            @if($incompleteMembers > 0)
                <svg class="w-4 h-4 {{ $incompleteColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            @else
                <svg class="w-4 h-4 {{ $incompleteColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
            
            {{-- نمایش تعداد اعضای ناقص --}}
            @if($incompleteMembers > 0)
                <span class="absolute -bottom-1 -right-1 bg-orange-500 text-white text-xs font-bold rounded-full w-4 h-4 flex items-center justify-center text-[10px]">
                    {{ $incompleteMembers }}
                </span>
            @endif
        </div>
        
        {{-- Tooltip --}}
        @if($showTooltips)
            <div class="tooltip-content absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 
                        transition-all duration-300 pointer-events-none z-50 group-hover:opacity-100 group-hover:pointer-events-auto">
                <div class="bg-gray-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap max-w-xs">
                    <div class="font-semibold">وضعیت اطلاعات اعضا</div>
                    <div class="text-xs mt-1">{{ $incompleteMessage }}</div>
                    @if($incompleteMembers > 0)
                        <div class="text-xs mt-1 border-t border-gray-600 pt-1">
                            {{ $totalMembers - $incompleteMembers }}/{{ $totalMembers }} عضو دارای اطلاعات کامل
                        </div>
                    @endif
                </div>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
            </div>
        @endif
    </div>

    {{-- آیکون درصد تکمیل مدارک بیماری خاص --}}
    @php
        // تعداد اعضایی که بیماری خاص دارند
        $membersNeedingDocument = 0;
        $membersWithDocument = 0;
        
        foreach ($family->members as $member) {
            if (is_array($member->problem_type) && in_array('special_disease', $member->problem_type)) {
                $membersNeedingDocument++;
                // بررسی وجود مدرک
                if ($member->getMedia('special_disease_documents')->count() > 0) {
                    $membersWithDocument++;
                }
            }
        }
        
        $documentCompletionPercentage = $membersNeedingDocument > 0 
            ? round(($membersWithDocument / $membersNeedingDocument) * 100) 
            : -1; // -1 یعنی نیازی به مدرک نیست
        
        if ($membersNeedingDocument === 0) {
            $documentStatus = 'none';
            $documentMessage = "هیچ عضوی بیماری خاص ندارد";
            $documentColors = ['bg_class' => 'bg-gray-100', 'border_class' => 'border-gray-300', 'icon_class' => 'text-gray-500', 'text_class' => 'text-gray-700'];
        } elseif ($membersWithDocument === $membersNeedingDocument) {
            $documentStatus = 'complete';
            $documentMessage = "همه مدارک بیماری خاص تکمیل شده است";
            $documentColors = $colorConfig['complete'] ?? ['bg_class' => 'bg-green-100', 'border_class' => 'border-green-300', 'icon_class' => 'text-green-600', 'text_class' => 'text-green-800'];
        } elseif ($membersWithDocument === 0) {
            $documentStatus = 'incomplete';
            $documentMessage = "{$membersNeedingDocument} عضو نیاز به مدرک بیماری خاص دارند";
            // استفاده از کلید 'incomplete' به صورت ایمن با ?? operator
            $documentColors = $colorConfig['incomplete'] ?? ['bg_class' => 'bg-red-100', 'border_class' => 'border-red-300', 'icon_class' => 'text-red-600', 'text_class' => 'text-red-800'];
        } else {
            $documentStatus = 'warning';
            $documentMessage = "{$membersWithDocument} از {$membersNeedingDocument} مدرک آپلود شده است";
            $documentColors = $colorConfig['warning'] ?? ['bg_class' => 'bg-orange-100', 'border_class' => 'border-orange-300', 'icon_class' => 'text-orange-600', 'text_class' => 'text-orange-800'];
        }
    @endphp
    
    @if($membersNeedingDocument > 0)
    <a href="{{ route('charity.families.show', $family->id) }}" class="relative group validation-icon-wrapper hover:scale-110 transition-transform">
        <div class="validation-icon {{ $iconSize }} {{ $documentColors['bg_class'] }} {{ $documentColors['border_class'] }} 
                    border-2 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-200 hover:z-30">
            {{-- آیکون مدرک --}}
            <svg class="w-4 h-4 {{ $documentColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            
            {{-- نمایش درصد --}}
            @if($documentCompletionPercentage >= 0 && $documentCompletionPercentage < 100)
                <span class="absolute -bottom-1 -right-1 bg-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center border text-[10px] {{ $documentColors['text_class'] }}">
                    {{ $documentCompletionPercentage }}%
                </span>
            @elseif($membersWithDocument < $membersNeedingDocument)
                <span class="absolute -bottom-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-4 h-4 flex items-center justify-center text-[10px]">
                    {{ $membersNeedingDocument - $membersWithDocument }}
                </span>
            @endif
        </div>
        
        {{-- Tooltip --}}
        @if($showTooltips)
            <div class="tooltip-content absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 
                        transition-all duration-300 pointer-events-none z-50 group-hover:opacity-100 group-hover:pointer-events-auto">
                <div class="bg-gray-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap max-w-xs">
                    <div class="font-semibold">مدارک بیماری خاص</div>
                    <div class="text-xs mt-1">{{ $documentMessage }}</div>
                    @if($membersNeedingDocument > 0)
                        <div class="text-xs mt-1 border-t border-gray-600 pt-1">
                            {{ $membersWithDocument }}/{{ $membersNeedingDocument }} مدرک آپلود شده - {{ $documentCompletionPercentage }}% تکمیل
                        </div>
                    @endif
                </div>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
            </div>
        @endif
    </a>
    @endif
</div>

{{-- استایل‌های CSS بهبود یافته --}}
<style>
.family-validation-icons-stacked {
    min-height: 120px;
    position: relative;
}

.family-validation-icons-stacked .validation-icon {
    position: relative;
    transition: all 0.2s ease-in-out;
    z-index: 10;
}

.family-validation-icons-stacked .validation-icon:hover {
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    z-index: 30;
}

.family-validation-icons-stacked .validation-icon-wrapper {
    position: relative;
}

.family-validation-icons-stacked .validation-icon-wrapper:hover {
    z-index: 40;
}

/* بهبود tooltip برای hover طولانی‌تر */
.family-validation-icons-stacked .group:hover .tooltip-content {
    opacity: 1;
    pointer-events: auto;
    animation: tooltipFadeIn 0.3s ease-out;
    transition-delay: 0.1s;
}

.family-validation-icons-stacked .tooltip-content:hover {
    opacity: 1;
    pointer-events: auto;
}

/* انیمیشن ظهور tooltip */
@keyframes tooltipFadeIn {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

/* رسپانسیو برای موبایل */
@media (max-width: 640px) {
    .family-validation-icons-stacked {
        min-height: 90px;
        gap: 2px;
    }
    
    .family-validation-icons-stacked .validation-icon {
        width: 20px !important;
        height: 20px !important;
    }
    
    .family-validation-icons-stacked .validation-icon svg {
        width: 12px !important;
        height: 12px !important;
    }
    
    .family-validation-icons-stacked .validation-icon span {
        font-size: 8px;
        width: 14px;
        height: 14px;
    }
    
    .family-validation-icons-stacked .tooltip-content {
        display: none; /* مخفی کردن tooltip در موبایل */
    }
}

/* بهبود z-index برای tooltip */
.family-validation-icons-stacked .tooltip-content {
    z-index: 9999;
}
</style> 