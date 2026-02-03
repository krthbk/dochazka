<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\HolidayController;
use App\Models\TeamMember;

Route::get('/', function () {
    $members = TeamMember::orderBy('name')->get();
    return view('welcome', compact('members'));
});


// vytvoření záznamu
Route::post('/attendance', [AttendanceController::class, 'store'])
    ->name('attendance.store');
// úprava existujícího záznamu
Route::patch('/attendance/{attendance}', [AttendanceController::class, 'update'])
    ->name('attendance.update');
// smazání záznamu
Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])
    ->name('attendance.destroy');
    

// ✅ kalendář bude číst sem (všichni členové)
Route::get('/attendance/events', [AttendanceController::class, 'events'])->name('attendance.events');

Route::get('/holidays/cz', [HolidayController::class, 'cz'])->name('holidays.cz');

