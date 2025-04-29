<div class="mb-8">
    <h3 class="text-lg font-bold mb-4 border-b pb-2">اعضای خانواده</h3>
    
    <!-- دکمه افزودن عضو جدید -->
    <div class="mb-4">
        <button type="button" wire:click="addMember" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            افزودن عضو خانواده
        </button>
    </div>
    
    <!-- لیست اعضای خانواده -->
    @forelse($members as $index => $member)
        <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold">عضو خانواده {{ $index + 1 }}</h4>
                <button type="button" wire:click="removeMember({{ $index }})" class="text-red-500 hover:text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">نسبت <span class="text-red-500 mr-1">*</span></label>
                    <select wire:model="members.{{ $index }}.relationship" class="border border-gray-300 rounded-md w-full py-2 px-3">
                        <option value="">انتخاب نسبت</option>
                        <option value="spouse">همسر</option>
                        <option value="child">فرزند</option>
                        <option value="parent">والدین</option>
                        <option value="sibling">خواهر/برادر</option>
                        <option value="other">سایر</option>
                    </select>
                    @error("members.{$index}.relationship") <span class="text-red-500 text-xs">این فیلد الزامی است.</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">نام <span class="text-red-500 mr-1">*</span></label>
                    <input type="text" wire:model.debounce.500ms="members.{{ $index }}.first_name" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام">
                    @error("members.{$index}.first_name") <span class="text-red-500 text-xs">این فیلد الزامی است.</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">نام خانوادگی <span class="text-red-500 mr-1">*</span></label>
                    <input type="text" wire:model.debounce.500ms="members.{{ $index }}.last_name" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام خانوادگی">
                    @error("members.{$index}.last_name") <span class="text-red-500 text-xs">این فیلد الزامی است.</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">تاریخ تولد <span class="text-red-500 mr-1">*</span></label>
                    <input type="text" wire:model.debounce.500ms="members.{{ $index }}.birth_date" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۳۸۰/۰۱/۰۱">
                    @error("members.{$index}.birth_date") <span class="text-red-500 text-xs">این فیلد الزامی است.</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">کد ملی <span class="text-red-500 mr-1">*</span></label>
                    <input type="text" wire:model.debounce.500ms="members.{{ $index }}.national_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۰ رقم بدون خط تیره">
                    @error("members.{$index}.national_code") <span class="text-red-500 text-xs">کد ملی معتبر و الزامی است.</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">شغل</label>
                    <input type="text" wire:model.debounce.500ms="members.{{ $index }}.occupation" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="شغل">
                    @error("members.{$index}.occupation") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">نوع مشکل</label>
                    <input type="text" wire:model.debounce.500ms="members.{{ $index }}.problem_type" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نوع مشکل">
                    @error("members.{$index}.problem_type") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">نوع بیمه</label>
                    <select wire:model="members.{{ $index }}.insurance_type" class="border border-gray-300 rounded-md w-full py-2 px-3">
                        <option value="">انتخاب نوع بیمه</option>
                        @foreach($insuranceTypes as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    @error("members.{$index}.insurance_type") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">مدرک الحاقی</label>
                    <input type="file" wire:model="members.{{ $index }}.attachment" class="border border-gray-300 rounded-md w-full py-2 px-3">
                    @error("members.{$index}.attachment") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="lg:col-span-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">شرایط خاص (برچسب‌گذاری)</label>
                    <div
                        x-data="{
                            input: '',
                            tags: @entangle('members.' . $index . '.special_conditions_tags').defer,
                            timer: null,
                            addTag() {
                                if (!Array.isArray(this.tags)) this.tags = [];
                                let input = this.input.trim().replace(/,$/, '');
                                let values = input.split(/[,،\s]+/).map(v => v.trim()).filter(v => v.length > 0);
                                values.forEach(val => {
                                    if(val && !this.tags.includes(val)) {
                                        this.tags.push(val);
                                    }
                                });
                                this.input = '';
                            },
                            onInput() {
                                clearTimeout(this.timer);
                                this.timer = setTimeout(() => this.addTag(), 3000);
                            },
                            onKey(e) {
                                if (e.key === ',' || e.key === 'Enter') {
                                    e.preventDefault();
                                    this.addTag();
                                }
                            },
                            removeTag(i) {
                                this.tags.splice(i, 1);
                            }
                        }"
                        class="w-full"
                    >
                        <input type="text"
                            x-model="input"
                            @input="onInput"
                            @keydown="onKey"
                            class="border border-gray-300 rounded-md w-full py-2 px-3 mb-2"
                            placeholder="شرایط خاص را وارد کنید و با اینتر یا ویرگول جدا کنید">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(tag, i) in tags" :key="i">
                                <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full">
                                    <span x-text="tag"></span>
                                    <button type="button" class="ml-1 text-blue-500 hover:text-red-500" @click="removeTag(i)">×</button>
                                </span>
                            </template>
                        </div>
                    </div>
                    @error("members.{$index}.special_conditions_tags") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500">
            <p>هنوز عضوی اضافه نشده است. با کلیک روی دکمه بالا اعضای خانواده را اضافه کنید.</p>
        </div>
    @endforelse
</div> 