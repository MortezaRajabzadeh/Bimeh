Route::middleware('can:view advanced reports')->get('/payment-details/{id}', [App\Http\Controllers\Insurance\FinancialReportController::class, 'paymentDetails'])->name('payment-details');
