@props(['member', 'size' => 'md'])

@php
    $sizeClasses = [
        'sm' => 'w-5 h-5',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8'
    ];
    
    $iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
    
    // بررسی اطلاعات هویتی
    $identityStatus = 'complete'; // green
    $identityTooltip = 'اطلاعات هویتی کامل است';
    
    if (empty($member->national_code) || empty($member->birth_date)) {
        $identityStatus = 'incomplete';
        $identityTooltip = 'اطلاعات هویتی ناقص است';
    }
    
    // بررسی مدرک بیماری خاص
    $showSpecialDiseaseIcon = false;
    $specialDiseaseStatus = 'none';
    $specialDiseaseTooltip = '';
    
    // بررسی از طریق problem_type (آرایه)
    if (is_array($member->problem_type) && in_array('special_disease', $member->problem_type)) {
        $showSpecialDiseaseIcon = true;
        
        // بررسی وجود مدرک در MediaLibrary
        $hasDocument = $member->getMedia('special_disease_documents')->count() > 0;
        
        if ($hasDocument) {
            $specialDiseaseStatus = 'complete'; // green
            $specialDiseaseTooltip = 'مدرک بیماری خاص ثبت شده است';
        } else {
            $specialDiseaseStatus = 'incomplete'; // red
            $specialDiseaseTooltip = 'مدرک بیماری خاص ثبت نشده است';
        }
    }
@endphp

<div class="flex items-center gap-2">
    {{-- آیکون اطلاعات هویتی --}}
    <div class="relative group">
        @if($identityStatus === 'complete')
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSize }} text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSize }} text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 17c-.77 1.333.192 3 1.732 3z" />
            </svg>
        @endif
        
        {{-- Tooltip --}}
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
            <div class="bg-gray-800 text-white text-xs rounded px-3 py-2 whitespace-nowrap">
                {{ $identityTooltip }}
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                    <div class="border-4 border-transparent border-t-gray-800"></div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- آیکون مدرک بیماری خاص (فقط اگر بیماری خاص دارد) --}}
    @if($showSpecialDiseaseIcon)
        <div class="relative group">
            @if($specialDiseaseStatus === 'complete')
                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSize }} text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSize }} text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 17c-.77 1.333.192 3 1.732 3z" />
                </svg>
            @endif
            
            {{-- Tooltip --}}
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                <div class="bg-gray-800 text-white text-xs rounded px-3 py-2 whitespace-nowrap">
                    {{ $specialDiseaseTooltip }}
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                        <div class="border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div> 