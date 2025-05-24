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
        <!-- جستجوی معیارهای از پیش تعریف شده -->
        <div class="mb-3">
            <label for="criteria_search" class="block mb-1 text-sm font-medium text-gray-700">جستجوی معیارها:</label>
            <input type="text" id="criteria_search" placeholder="عبارت مورد نظر را وارد کنید..." class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block w-full text-sm mb-2">
        </div>
        
        <!-- لیست معیارهای از پیش تعریف شده -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-3">
            @php
                $predefinedCriteria = [
                    'از کار افتادگی', 'سرپرست خانوار زن', 'بیماری خاص', 'یتیم', 
                    'بی سرپرست', 'معلولیت', 'بیکاری', 'سالمند', 'کودکان کار', 'ساکن مناطق محروم'
                ];
            @endphp
            
            @foreach($predefinedCriteria as $criteria)
                <div class="flex items-center criteria-item">
                    <input type="checkbox" name="criteria[]" value="{{ $criteria }}" id="criteria_{{ $loop->index }}"
                        @if(is_array($family->acceptance_criteria) && in_array($criteria, $family->acceptance_criteria)) checked @endif
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="criteria_{{ $loop->index }}" class="mr-2 text-sm text-gray-700 criteria-label">{{ $criteria }}</label>
                </div>
            @endforeach
        </div>
        
        <!-- افزودن معیار سفارشی -->
        <div>
            <label for="custom_criteria" class="block mb-1 text-sm font-medium text-gray-700">معیار سفارشی:</label>
            <div class="flex gap-2 relative">
                <input type="text" id="custom_criteria" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block w-full text-sm" autocomplete="off">
                <button type="button" onclick="addCustomCriteria()" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">افزودن</button>
                
                <!-- لیست پیشنهادی -->
                <div id="suggestions" class="absolute top-full right-0 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-sm hidden z-10 max-h-48 overflow-y-auto"></div>
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
        
        // جستجوی لایو در معیارهای پذیرش
        const searchInput = document.getElementById('criteria_search');
        if (searchInput) {
            searchInput.addEventListener('input', filterCriteria);
        }
        
        // پیشنهاد خودکار برای فیلد معیار سفارشی
        const customInput = document.getElementById('custom_criteria');
        const suggestionsList = document.getElementById('suggestions');
        
        if (customInput && suggestionsList) {
            // لیست معیارهای پیشنهادی
            const allSuggestions = [
                'از کار افتادگی', 'سرپرست خانوار زن', 'بیماری خاص', 'یتیم', 
                'بی سرپرست', 'معلولیت', 'بیکاری', 'سالمند', 'کودکان کار', 'ساکن مناطق محروم',
                'مشکلات اقتصادی', 'خانواده پرجمعیت', 'مادر باردار', 'کودک معلول',
                'بیماری صعب‌العلاج', 'مهاجر', 'آسیب‌دیده از حوادث طبیعی'
            ];
            
            // نمایش پیشنهادها براساس متن وارد شده
            customInput.addEventListener('input', function() {
                const value = this.value.trim().toLowerCase();
                if (value.length > 1) {
                    // فیلتر کردن پیشنهادهای مطابق
                    const filteredSuggestions = allSuggestions.filter(item => 
                        item.toLowerCase().includes(value) && 
                        !isAlreadySelected(item)
                    );
                    
                    if (filteredSuggestions.length > 0) {
                        displaySuggestions(filteredSuggestions);
                    } else {
                        suggestionsList.classList.add('hidden');
                    }
                } else {
                    suggestionsList.classList.add('hidden');
                }
            });
            
            // مخفی کردن پیشنهادها با کلیک خارج از لیست
            document.addEventListener('click', function(e) {
                if (!customInput.contains(e.target) && !suggestionsList.contains(e.target)) {
                    suggestionsList.classList.add('hidden');
                }
            });
            
            // نمایش دوباره پیشنهادها با فوکوس بر روی فیلد
            customInput.addEventListener('focus', function() {
                if (this.value.trim().length > 1) {
                    const value = this.value.trim().toLowerCase();
                    const filteredSuggestions = allSuggestions.filter(item => 
                        item.toLowerCase().includes(value) && 
                        !isAlreadySelected(item)
                    );
                    
                    if (filteredSuggestions.length > 0) {
                        displaySuggestions(filteredSuggestions);
                    }
                }
            });
            
            // اضافه کردن با فشردن دکمه Enter
            customInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addCustomCriteria();
                }
            });
        }
    });
    
    // بررسی آیا معیار قبلا انتخاب شده است
    function isAlreadySelected(criteria) {
        const checkboxes = document.querySelectorAll('input[name="criteria[]"]:checked');
        return Array.from(checkboxes).some(cb => cb.value === criteria);
    }
    
    // نمایش پیشنهادها در لیست
    function displaySuggestions(suggestions) {
        const suggestionsList = document.getElementById('suggestions');
        suggestionsList.innerHTML = '';
        
        suggestions.forEach(item => {
            const div = document.createElement('div');
            div.className = 'px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm text-right';
            div.textContent = item;
            
            div.addEventListener('click', function() {
                document.getElementById('custom_criteria').value = item;
                suggestionsList.classList.add('hidden');
                addCustomCriteria();
            });
            
            suggestionsList.appendChild(div);
        });
        
        suggestionsList.classList.remove('hidden');
    }
    
    // فیلتر کردن معیارهای پذیرش براساس متن جستجو شده
    function filterCriteria() {
        const searchTerm = document.getElementById('criteria_search').value.trim().toLowerCase();
        const criteriaItems = document.querySelectorAll('.criteria-item');
        
        criteriaItems.forEach(item => {
            const label = item.querySelector('.criteria-label');
            const text = label.textContent.toLowerCase();
            
            if (searchTerm === '' || text.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // افزودن معیار سفارشی
    function addCustomCriteria() {
        const customInput = document.getElementById('custom_criteria');
        if (customInput.value.trim() !== '') {
            // بررسی تکراری نبودن
            if (isAlreadySelected(customInput.value.trim())) {
                alert('این معیار قبلاً انتخاب شده است.');
                return;
            }
            
            // ایجاد چک‌باکس جدید
            const container = document.createElement('div');
            container.className = 'flex items-center mt-2 criteria-item';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'criteria[]';
            checkbox.value = customInput.value.trim();
            checkbox.checked = true;
            checkbox.className = 'h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500';
            
            const label = document.createElement('label');
            label.className = 'mr-2 text-sm text-gray-700 criteria-label';
            label.textContent = customInput.value.trim();
            
            container.appendChild(checkbox);
            container.appendChild(label);
            
            // افزودن به لیست
            document.querySelector('.grid').appendChild(container);
            
            // پاک کردن فیلد ورودی
            customInput.value = '';
            
            // مخفی کردن لیست پیشنهادی
            document.getElementById('suggestions').classList.add('hidden');
        }
    }
    
    // آپدیت فیلد مخفی با مقادیر انتخاب شده
    function updateCriteriaJson() {
        const checkboxes = document.querySelectorAll('input[name="criteria[]"]:checked');
        const values = Array.from(checkboxes).map(cb => cb.value);
        document.getElementById('criteria_json').value = values.join(',');
    }
</script> 