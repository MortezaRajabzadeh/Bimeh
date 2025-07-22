<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * ویژگی مدیریت آپلود و بهینه‌سازی تصاویر
 */
trait HandlesImageUploads
{
    /**
     * آپلود و بهینه‌سازی تصویر
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $directory
     * @param  int  $width
     * @param  int  $height
     * @param  int  $quality
     * @return string
     */
    protected function uploadAndOptimizeImage($file, $directory = 'uploads', $width = 800, $height = 800, $quality = 75)
    {
        // ایجاد نام فایل منحصر به فرد
        $filename = uniqid() . '.webp';

        // مسیر کامل ذخیره‌سازی
        $path = $directory . '/' . $filename;

        // ایجاد مسیر در صورت عدم وجود در disk عمومی
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }

        // پردازش تصویر
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file)
            ->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->toWebp($quality);

        // ذخیره تصویر در disk عمومی
        Storage::disk('public')->put($path, $image);

        return $path;
    }

    /**
     * حذف تصویر قبلی در صورت وجود
     *
     * @param  string|null  $path
     * @return void
     */
    protected function deleteImageIfExists($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
