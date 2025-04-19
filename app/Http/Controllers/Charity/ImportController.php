<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Imports\FamiliesImport;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class ImportController extends Controller
{
    /**
     * نمایش فرم آپلود فایل اکسل
     */
    public function index()
    {
        Gate::authorize('create family');
        
        $regions = Region::active()->get();
        
        return view('charity.import.index', compact('regions'));
    }

    /**
     * آپلود و پردازش فایل اکسل
     */
    public function import(Request $request)
    {
        Gate::authorize('create family');
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'region_id' => 'required|exists:regions,id',
        ], [
            'file.required' => 'انتخاب فایل الزامی است.',
            'file.file' => 'فایل انتخاب شده معتبر نیست.',
            'file.mimes' => 'فرمت فایل باید xlsx, xls یا csv باشد.',
            'file.max' => 'حجم فایل نباید بیشتر از 10 مگابایت باشد.',
            'region_id.required' => 'انتخاب منطقه الزامی است.',
            'region_id.exists' => 'منطقه انتخاب شده معتبر نیست.',
        ]);

        try {
            $import = new FamiliesImport(
                $request->user(),
                $request->input('region_id')
            );
            
            ExcelFacade::import($import, $request->file('file'));
            
            $results = $import->getResults();
            
            return redirect()->route('charity.import.index')
                ->with('success', "فایل با موفقیت آپلود شد. تعداد {$results['success']} خانواده با موفقیت ثبت شد.")
                ->with('results', $results);
                
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'خطا در پردازش فایل: ' . $e->getMessage()]);
        }
    }

    /**
     * دانلود فایل نمونه
     */
    public function downloadTemplate()
    {
        Gate::authorize('create family');
        
        $filePath = storage_path('app/templates/families_template.xlsx');
        
        if (!file_exists($filePath)) {
            abort(404, 'فایل نمونه یافت نشد.');
        }
        
        return response()->download($filePath, 'template_families.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
} 