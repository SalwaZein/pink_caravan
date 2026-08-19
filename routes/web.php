<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\ClinicPatientController;
use App\Http\Controllers\EmiratesIdController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\MammographerController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ServiceBookingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Language toggle (writes session locale, then returns to the previous page).
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ar'], true)) {
        session(['locale' => $locale]);
    }
    return back();
})->where('locale', 'en|ar')->name('lang.switch');

// Hub / role selection (public landing)
Route::get('/', [PageController::class, 'hub'])->name('hub');

// Public report verification (no login) — confirms a report is authentic.
Route::get('/verify',        [ReportController::class, 'verifyForm'])->name('verify');
Route::post('/verify/check', [ReportController::class, 'verifyCheck'])->middleware('throttle:20,1')->name('verify.check');

// ---- Authentication (staff: email + password) ----
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Public — booking + patient (no login) ----
Route::get('/booking',  [PageController::class, 'booking'])->name('booking');
Route::post('/booking', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');

// Patient OTP access + report (no password)
Route::get('/patient',                  [PatientController::class, 'access'])->name('patient');
Route::post('/patient/otp',             [PatientController::class, 'sendOtp'])->middleware('throttle:6,1')->name('patient.otp.send');
Route::get('/patient/otp',              [PatientController::class, 'otpForm'])->name('patient.otp');
Route::post('/patient/verify',          [PatientController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('patient.verify');
Route::get('/patient/report',           [PatientController::class, 'report'])->name('patient.report');
Route::get('/patient/report/document',  [PatientController::class, 'document'])->name('patient.report.document');
Route::get('/patient/report/download',  [PatientController::class, 'download'])->name('patient.report.download');
Route::get('/patient/reset',            [PatientController::class, 'reset'])->name('patient.reset');

// Emirates ID reader — dev mock endpoint (used when no local reader bridge is configured).
Route::middleware('auth')->get('/tools/emirates-id/read', [EmiratesIdController::class, 'read'])->name('tools.eid.read');

// ---- Nurse (auth) ----
Route::middleware(['auth', 'role:nurse'])->group(function () {
    Route::get('/nurse/queue',    [PageController::class, 'nurseQueue'])->name('nurse.queue');
    Route::get('/nurse/patients', [PageController::class, 'nursePatients'])->name('nurse.patients');

    // Patient History & Record Sheet (Form 3) — needs the fill_record_sheet permission
    Route::middleware('can:fill_record_sheet')->group(function () {
        Route::get('/nurse/record',                [RecordController::class, 'create'])->name('nurse.record');
        Route::post('/nurse/record',               [RecordController::class, 'store'])->name('nurse.record.store');
        Route::get('/nurse/record/{record}/edit',  [RecordController::class, 'edit'])->name('nurse.record.edit');
        Route::put('/nurse/record/{record}',       [RecordController::class, 'update'])->name('nurse.record.update');
    });
});

// ---- Doctor (auth) ----
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/doctor/assigned',  [PageController::class, 'doctorAssigned'])->name('doctor.assigned');
    Route::get('/doctor/completed', [PageController::class, 'doctorCompleted'])->name('doctor.completed');

    // "Clinical exam" nav with no patient chosen → send to the assigned list.
    Route::get('/doctor/exam', fn () => redirect()->route('doctor.assigned'))->name('doctor.exam.index');

    // Clinical Breast Examination (Form 2) — needs the perform_clinical_exam permission
    Route::middleware('can:perform_clinical_exam')->group(function () {
        Route::get('/doctor/exam/{record}',  [ExamController::class, 'edit'])->name('doctor.exam');
        Route::put('/doctor/exam/{record}',  [ExamController::class, 'update'])->name('doctor.exam.update');
        Route::get('/doctor/exam/{record}/report', [ExamController::class, 'report'])->name('doctor.exam.report');
    });
});

// ---- Mammographer (auth) — manual PC number + post-campaign mammogram report ----
Route::middleware(['auth', 'can:manage_mammograms'])->group(function () {
    Route::get('/mammographer/queue',              [MammographerController::class, 'queue'])->name('mammographer.queue');
    Route::get('/mammographer/record/{record}',    [MammographerController::class, 'edit'])->name('mammographer.record');
    Route::put('/mammographer/record/{record}',    [MammographerController::class, 'update'])->name('mammographer.record.update');
    Route::get('/mammographer/record/{record}/report', [MammographerController::class, 'viewReport'])->name('mammographer.record.report');
    Route::post('/mammographer/record/{record}/send', [MammographerController::class, 'send'])->name('mammographer.record.send');
});

// ---- Clinic administrator (auth) ----
Route::middleware(['auth', 'role:clinic_admin'])->group(function () {
    Route::get('/clinic/queue',    [PageController::class, 'clinicQueue'])->name('clinic.queue');
    Route::get('/clinic/register', [PageController::class, 'clinicRegister'])->name('clinic.register');
    Route::post('/clinic/register', [ClinicPatientController::class, 'store'])
        ->middleware('can:register_patients')->name('clinic.register.store');
    Route::get('/clinic/assign',   [PageController::class, 'clinicAssign'])->name('clinic.assign');
    Route::get('/clinic/reports',  [PageController::class, 'clinicReports'])->name('clinic.reports');

    Route::post('/clinic/assign', [AssignmentController::class, 'assign'])
        ->middleware('can:assign_doctors')->name('clinic.assign.store');
    Route::post('/clinic/complete/{record}', [AssignmentController::class, 'complete'])
        ->middleware('can:assign_doctors')->name('clinic.complete');
});

// ---- Report document (auth; staff who examined/manage/report on the record) ----
Route::middleware('auth')->get('/reports/{record}/document', [ReportController::class, 'document'])->name('reports.document');

// ---- Administration area (auth + specific permissions, not tied to one role) ----
Route::middleware('auth')->group(function () {
    Route::get('/super/dashboard', [PageController::class, 'superDashboard'])->middleware('can:view_dashboards')->name('super.dashboard');
    Route::get('/super/audit',     [PageController::class, 'superAudit'])->middleware('can:view_audit')->name('super.audit');
    Route::get('/super/export/csv', [ExportController::class, 'csv'])->middleware('can:view_dashboards')->name('super.export.csv');
    Route::get('/super/export/pdf', [ExportController::class, 'pdf'])->middleware('can:view_dashboards')->name('super.export.pdf');

    // Service bookings — review workflow (Administration). Each action gated by its own permission.
    Route::prefix('super/bookings')->name('super.bookings.')->group(function () {
        Route::get('/',                  [ServiceBookingController::class, 'index'])->middleware('can:review_bookings')->name('index');
        Route::get('/dashboard',         [ServiceBookingController::class, 'dashboard'])->middleware('can:review_bookings')->name('dashboard');
        Route::get('/{booking}',         [ServiceBookingController::class, 'show'])->middleware('can:review_bookings')->whereNumber('booking')->name('show');
        Route::get('/{booking}/report',  [ServiceBookingController::class, 'report'])->middleware('can:review_bookings')->whereNumber('booking')->name('report');
        Route::post('/{booking}/approve',  [ServiceBookingController::class, 'approve'])->middleware('can:approve_bookings')->whereNumber('booking')->name('approve');
        Route::post('/{booking}/reject',   [ServiceBookingController::class, 'reject'])->middleware('can:approve_bookings')->whereNumber('booking')->name('reject');
        Route::post('/{booking}/paid',     [ServiceBookingController::class, 'markPaid'])->middleware('can:mark_bookings_paid')->whereNumber('booking')->name('paid');
        Route::post('/{booking}/complete', [ServiceBookingController::class, 'complete'])->middleware('can:complete_bookings')->whereNumber('booking')->name('complete');
    });

    // Clinics (Phase 1a — functional)
    Route::middleware('can:manage_clinics')->prefix('super/clinics')->name('super.clinics.')->group(function () {
        Route::get('/',                      [ClinicController::class, 'index'])->name('index');
        Route::get('/create',                [ClinicController::class, 'create'])->name('create');
        Route::post('/',                     [ClinicController::class, 'store'])->name('store');
        Route::get('/{clinic}/edit',         [ClinicController::class, 'edit'])->name('edit');
        Route::put('/{clinic}',              [ClinicController::class, 'update'])->name('update');
        Route::patch('/{clinic}/activate',   [ClinicController::class, 'activate'])->name('activate');
        Route::patch('/{clinic}/deactivate', [ClinicController::class, 'deactivate'])->name('deactivate');
    });

    // Users & roles (Phase 1b — functional)
    Route::middleware('can:manage_users')->prefix('super/users')->name('super.users.')->group(function () {
        Route::get('/',            [UserController::class, 'index'])->name('index');
        Route::get('/create',      [UserController::class, 'create'])->name('create');
        Route::post('/',           [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}',      [UserController::class, 'update'])->name('update');
    });
});
