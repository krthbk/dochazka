<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\HolidayController;
use App\Models\TeamMember;

Route::get('/', function () {
    $members = TeamMember::orderBy('name')->get();
    return view('welcome', compact('members'));
});

Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

// ✅ kalendář bude číst sem (všichni členové)
Route::get('/attendance/events', [AttendanceController::class, 'events'])->name('attendance.events');

Route::get('/holidays/cz', [HolidayController::class, 'cz'])->name('holidays.cz');

