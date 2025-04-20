/**
  بخش مربوط به معیارهای پذیرش چندگانه - این کد باید در فرم ویرایش خانواده قرار گیرد
*/

<!-- معیارهای پذیرش -->
<div class="mb-4">
    <label for="acceptance_criteria" class="block mb-2 text-sm font-medium text-gray-700">معیارهای پذیرش:</label>
    
    <!-- نمایش معیارهای فعلی -->
    <div class="mb-2">
        <p class="text-sm mb-1">معیارهای فعلی:</p>
        <div class="flex flex-wrap gap-1">
            @if(is_array($family->acceptance_criteria))
                @foreach($family->acceptance_criteria as $criteria)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ $criteria }}
                    </span>
                @endforeach
            @else
                <span class="text-gray-500 text-sm">معیاری ثبت نشده است</span>
            @endif
        </div>
    </div>
    
    <!-- ورود معیارهای جدید -->
    <div class="mt-3">
        <!-- لیست معیارهای از پیش تعریف شده -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-3">
            @php
                $predefinedCriteria = [
                    'از کار افتادگی', 'سرپرست خانوار زن', 'بیماری خاص', 'یتیم', 
                    'بی سرپرست', 'معلولیت', 'بیکاری', 'سالمند', 'کودکان کار', 'ساکن مناطق محروم'
                ];
            @endphp
            
            @foreach($predefinedCriteria as $criteria)
                <div class="flex items-center">
                    <input type="checkbox" name="criteria[]" value="{{ $criteria }}" id="criteria_{{ $loop->index }}"
                        @if(is_array($family->acceptance_criteria) && in_array($criteria, $family->acceptance_criteria)) checked @endif
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="criteria_{{ $loop->index }}" class="mr-2 text-sm text-gray-700">{{ $criteria }}</label>
                </div>
            @endforeach
        </div>
        
        <!-- افزودن معیار سفارشی -->
        <div>
            <label for="custom_criteria" class="block mb-1 text-sm font-medium text-gray-700">معیار سفارشی:</label>
            <div class="flex gap-2">
                <input type="text" id="custom_criteria" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block w-full text-sm">
                <button type="button" onclick="addCustomCriteria()" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">افزودن</button>
            </div>
        </div>
        
        <!-- فیلد مخفی برای ذخیره معیارها -->
        <input type="hidden" name="acceptance_criteria_array" id="criteria_json">
    </div>
</div>

<!-- اسکریپت جاوااسکریپت برای پردازش معیارها -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // آپدیت فیلد مخفی قبل از ارسال فرم
        document.querySelector('form').addEventListener('submit', function() {
            updateCriteriaJson();
        });
    });
    
    // افزودن معیار سفارشی
    function addCustomCriteria() {
        const customInput = document.getElementById('custom_criteria');
        if (customInput.value.trim() !== '') {
            // ایجاد چک‌باکس جدید
            const container = document.createElement('div');
            container.className = 'flex items-center mt-2';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'criteria[]';
            checkbox.value = customInput.value.trim();
            checkbox.checked = true;
            checkbox.className = 'h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500';
            
            const label = document.createElement('label');
            label.className = 'mr-2 text-sm text-gray-700';
            label.textContent = customInput.value.trim();
            
            container.appendChild(checkbox);
            container.appendChild(label);
            
            // افزودن به لیست
            document.querySelector('.grid').appendChild(container);
            
            // پاک کردن فیلد ورودی
            customInput.value = '';
        }
    }
    
    // آپدیت فیلد مخفی با مقادیر انتخاب شده
    function updateCriteriaJson() {
        const checkboxes = document.querySelectorAll('input[name="criteria[]"]:checked');
        const values = Array.from(checkboxes).map(cb => cb.value);
        document.getElementById('criteria_json').value = values.join(',');
    }
</script> 