@if(isset($impersonating))
<div class="bg-yellow-100 border-t-4 border-yellow-500 rounded-b text-yellow-900 px-4 py-3 shadow-md mb-4" role="alert">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold">حالت مشاهده نقش</p>
                <p class="text-sm">شما در حال مشاهده سیستم با نقش <span class="font-bold">{{ $impersonating['display_name'] }}</span> هستید.</p>
            </div>
        </div>
        <div>
            <form action="{{ route('admin.switch-role.destroy') }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm">
                    بازگشت به ادمین
                </button>
            </form>
        </div>
    </div>
</div>
@endif