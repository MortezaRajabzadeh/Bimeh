@props(['family', 'showActions' => true])

@php
    use App\Helpers\FamilyValidationHelper;
    
    $overallData = FamilyValidationHelper::calculateOverallScore($family);
    $messages = FamilyValidationHelper::getUserFriendlyMessages($overallData['validation_data']);
    $readinessData = FamilyValidationHelper::isReadyForApproval($family);
    $colorConfig = config('ui.status_colors');
    $overallColors = $colorConfig[$overallData['overall_status']] ?? $colorConfig['unknown'];
@endphp

<div class="family-validation-detail bg-white border border-gray-200 rounded-lg p-6 space-y-6">
    {{-- هدر با نمره کلی --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="validation-score-circle w-16 h-16 {{ $overallColors['bg_class'] }} {{ $overallColors['border_class'] }} 
                        border-4 rounded-full flex items-center justify-center">
                <span class="text-2xl font-bold {{ $overallColors['text_class'] }}">
                    {{ $overallData['overall_score'] }}
                </span>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">وضعیت اعتبارسنجی خانواده</h3>
                <p class="text-sm text-gray-600">{{ $readinessData['message'] }}</p>
            </div>
        </div>
        
        {{-- نشان آمادگی --}}
        @if($readinessData['is_ready'])
            <div class="flex items-center gap-2 bg-green-100 text-green-800 px-3 py-2 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="font-medium">آماده تایید</span>
            </div>
        @else
            <div class="flex items-center gap-2 bg-orange-100 text-orange-800 px-3 py-2 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">نیاز به تکمیل</span>
            </div>
        @endif
    </div>

    {{-- جزئیات هر بخش --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- اطلاعات هویتی --}}
        @php 
            $identity = $overallData['validation_data']['identity'];
            $identityColors = $colorConfig[$identity['status']] ?? $colorConfig['unknown'];
        @endphp
        <div class="validation-section border {{ $identityColors['border_class'] }} rounded-lg p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 {{ $identityColors['bg_class'] }} rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $identityColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">اطلاعات هویتی</h4>
                    <p class="text-sm {{ $identityColors['text_class'] }}">{{ $identity['percentage'] }}% تکمیل</p>
                </div>
            </div>
            
            <div class="space-y-2">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300 {{ $identityColors['bg_class'] }}" 
                         style="width: {{ $identity['percentage'] }}%"></div>
                </div>
                <p class="text-xs text-gray-600">{{ $identity['message'] }}</p>
                
                {{-- جزئیات اعضا --}}
                @if(!empty($identity['details']))
                    <details class="mt-3">
                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                            جزئیات اعضا ({{ $identity['complete_members'] }}/{{ $identity['total_members'] }})
                        </summary>
                        <div class="mt-2 space-y-1">
                            @foreach($identity['details'] as $member)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="{{ $member['is_head'] ? 'font-semibold' : '' }}">
                                        {{ $member['name'] }} {{ $member['is_head'] ? '(سرپرست)' : '' }}
                                    </span>
                                    <span class="text-{{ $member['completion_rate'] === 100 ? 'green' : ($member['completion_rate'] >= 50 ? 'yellow' : 'red') }}-600">
                                        {{ $member['completion_rate'] }}%
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>

        {{-- وضعیت محرومیت --}}
        @php 
            $location = $overallData['validation_data']['location'];
            $locationColors = $colorConfig[$location['status']] ?? $colorConfig['unknown'];
        @endphp
        <div class="validation-section border {{ $locationColors['border_class'] }} rounded-lg p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 {{ $locationColors['bg_class'] }} rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $locationColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">موقعیت جغرافیایی</h4>
                    <p class="text-sm {{ $locationColors['text_class'] }}">
                        @if($location['status'] === 'complete') غیرمحروم
                        @elseif($location['status'] === 'none') محروم
                        @else نامشخص
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="space-y-2">
                <p class="text-xs text-gray-600">{{ $location['message'] }}</p>
                
                @if($location['province_name'])
                    <div class="text-xs text-gray-500">
                        <span class="font-medium">استان:</span> {{ $location['province_name'] }}
                        @if($location['deprivation_rank'])
                            <br><span class="font-medium">رتبه محرومیت:</span> {{ $location['deprivation_rank'] }}
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- مدارک --}}
        @php 
            $documents = $overallData['validation_data']['documents'];
            $documentsColors = $colorConfig[$documents['status']] ?? $colorConfig['unknown'];
        @endphp
        <div class="validation-section border {{ $documentsColors['border_class'] }} rounded-lg p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 {{ $documentsColors['bg_class'] }} rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $documentsColors['icon_class'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">مدارک</h4>
                    <p class="text-sm {{ $documentsColors['text_class'] }}">{{ $documents['percentage'] }}% تکمیل</p>
                </div>
            </div>
            
            <div class="space-y-2">
                @if($documents['members_requiring_docs'] > 0)
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-300 {{ $documentsColors['bg_class'] }}" 
                             style="width: {{ $documents['percentage'] }}%"></div>
                    </div>
                @endif
                <p class="text-xs text-gray-600">{{ $documents['message'] }}</p>
                
                {{-- جزئیات مدارک --}}
                @if(!empty($documents['details']))
                    <details class="mt-3">
                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                            جزئیات مدارک ({{ $documents['members_with_complete_docs'] }}/{{ $documents['members_requiring_docs'] }})
                        </summary>
                        <div class="mt-2 space-y-1">
                            @foreach($documents['details'] as $member)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="{{ $member['is_head'] ? 'font-semibold' : '' }}">
                                        {{ $member['name'] }} {{ $member['is_head'] ? '(سرپرست)' : '' }}
                                    </span>
                                    <span class="text-{{ $member['completion_rate'] === 100 ? 'green' : ($member['completion_rate'] >= 50 ? 'yellow' : 'red') }}-600">
                                        {{ $member['completion_rate'] }}%
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>
    </div>

    {{-- پیام‌های خلاصه --}}
    <div class="validation-messages space-y-2">
        <h4 class="font-semibold text-gray-900 mb-3">خلاصه وضعیت:</h4>
        @foreach($messages as $message)
            <div class="flex items-start gap-2 text-sm">
                <span class="mt-0.5">{{ $message }}</span>
            </div>
        @endforeach
    </div>

    {{-- اقدامات پیشنهادی --}}
    @if($showActions && !$readinessData['is_ready'])
        <div class="validation-actions border-t border-gray-200 pt-4">
            <h4 class="font-semibold text-gray-900 mb-3">اقدامات لازم:</h4>
            <div class="space-y-2">
                @forelse($readinessData['required_actions'] as $action)
                    <div class="flex items-start gap-2 text-sm text-orange-700 bg-orange-50 p-2 rounded">
                        <svg class="w-4 h-4 mt-0.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $action }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">هیچ اقدام خاصی مورد نیاز نیست.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>

{{-- استایل‌های CSS اضافی --}}
<style>
.family-validation-detail .validation-score-circle {
    position: relative;
    transition: all 0.3s ease;
}

.family-validation-detail .validation-score-circle:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.family-validation-detail .validation-section {
    transition: all 0.2s ease;
}

.family-validation-detail .validation-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.family-validation-detail details[open] summary {
    margin-bottom: 8px;
}

.family-validation-detail details summary::-webkit-details-marker {
    display: none;
}

.family-validation-detail details summary::before {
    content: '▶';
    display: inline-block;
    margin-left: 4px;
    transition: transform 0.2s ease;
}

.family-validation-detail details[open] summary::before {
    transform: rotate(90deg);
}

/* انیمیشن progress bar */
.family-validation-detail .validation-section .h-2 {
    transition: width 1s ease-in-out;
}

@media (max-width: 768px) {
    .family-validation-detail .grid-cols-3 {
        grid-template-columns: 1fr;
    }
    
    .family-validation-detail .validation-score-circle {
        width: 48px;
        height: 48px;
    }
    
    .family-validation-detail .validation-score-circle span {
        font-size: 1.25rem;
    }
}
</style>

@php
    $totalMembers = $family->members->count();
    $membersNeedingDocument = 0;
    $incompleteMembers = 0;

    foreach ($family->members as $member) {
        if (is_array($member->problem_type) && in_array('special_disease', $member->problem_type)) {
            $membersNeedingDocument++;
            if ($member->getMedia('special_disease_documents')->count() === 0) {
                $incompleteMembers++;
            }
        }
    }

    $completeMembers = $totalMembers - $incompleteMembers;
    $docPercentage = $totalMembers > 0 ? round(($completeMembers / $totalMembers) * 100) : 100;

    if ($membersNeedingDocument === 0 || $docPercentage === 100) {
        $docStatus = 'complete';
        $docMessage = "مدارک بیماری خاص کامل است";
        $docColors = $colorConfig['complete'];
    } elseif ($docPercentage > 0) {
        $docStatus = 'partial';
        $docMessage = "{$completeMembers} از {$totalMembers} عضو کامل";
        $docColors = $colorConfig['partial'];
    } else {
        $docStatus = 'none';
        $docMessage = "هیچ مدرکی آپلود نشده";
        $docColors = $colorConfig['none'];
    }
@endphp