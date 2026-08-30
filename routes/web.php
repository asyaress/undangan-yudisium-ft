<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminInvitationPlaygroundController;
use App\Http\Controllers\AdminParticipantController;
use App\Http\Controllers\AdminPeriodController;
use App\Http\Controllers\AdminRecipientController;
use App\Http\Controllers\AdminStudyProgramController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvitationPlaygroundController;
use App\Http\Controllers\InvitationResponseController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ParticipantImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InvitationController::class, 'show'])->name('home');
Route::get('/undangan/{slug?}', [InvitationController::class, 'show'])->name('undangan.show');
Route::get('/playground-undangan', InvitationPlaygroundController::class)->name('undangan.playground');
Route::post('/undangan/verify-nim', [InvitationController::class, 'verifyNim'])->name('undangan.verify-nim');
Route::post('/undangan/verify-recipient', [InvitationController::class, 'verifyRecipient'])->name('undangan.verify-recipient');
Route::post('/undangan/confirm-student', [InvitationController::class, 'confirmStudent'])->name('undangan.confirm-student');
Route::post('/undangan/clear-student', [InvitationController::class, 'clearStudent'])->name('undangan.clear-student');

Route::get('/checkin/{slug?}', [CheckinController::class, 'index'])->name('checkin.form');
Route::post('/checkin/search', [CheckinController::class, 'search'])->middleware('throttle:checkin-public')->name('checkin.search');
Route::post('/checkin/confirm', [CheckinController::class, 'confirm'])->middleware('throttle:checkin-public')->name('checkin.confirm');
Route::post('/rsvp/participant', [InvitationResponseController::class, 'participant'])->name('rsvp.participant');
Route::post('/rsvp/recipient', [InvitationResponseController::class, 'recipient'])->name('rsvp.recipient');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::redirect('/monitoring', '/monitoring/mahasiswa')->name('monitoring.index');
    Route::get('/monitoring/mahasiswa', [MonitoringController::class, 'mahasiswa'])->name('monitoring.mahasiswa');
    Route::get('/monitoring/private', [MonitoringController::class, 'private'])->name('monitoring.private');
    Route::get('/monitoring/private/signature/{recipient}', [MonitoringController::class, 'signature'])->name('monitoring.private.signature');
    Route::get('/monitoring/{type}/live', [MonitoringController::class, 'live'])->name('monitoring.live');
    Route::get('/monitoring/{type}/export', [MonitoringController::class, 'export'])->name('monitoring.export');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/invitation-playground', AdminInvitationPlaygroundController::class)->name('invitation-playground');
        Route::get('/events', [AdminPeriodController::class, 'index'])->name('events.index');
        Route::get('/events/create', [AdminPeriodController::class, 'create'])->name('events.create');
        Route::get('/events/{period}/edit', [AdminPeriodController::class, 'edit'])->name('events.edit');
        Route::post('/periods', [AdminPeriodController::class, 'store'])->name('periods.store');
        Route::put('/periods/{period}', [AdminPeriodController::class, 'update'])->name('periods.update');
        Route::get('/study-programs', [AdminStudyProgramController::class, 'index'])->name('study-programs.index');
        Route::post('/study-programs', [AdminStudyProgramController::class, 'store'])->name('study-programs.store');
        Route::put('/study-programs/{studyProgram}', [AdminStudyProgramController::class, 'update'])->name('study-programs.update');
        Route::get('/checkin/scanner', [CheckinController::class, 'scannerIndex'])->name('checkin.scanner.index');
        Route::get('/checkin/manual', [CheckinController::class, 'manualIndex'])->name('checkin.manual.index');
        Route::post('/checkin/manual/search', [CheckinController::class, 'manualSearch'])->name('checkin.manual.search');
        Route::post('/checkin/manual/confirm', [CheckinController::class, 'manualConfirm'])->name('checkin.manual.confirm');
        Route::post('/checkin/manual/scan', [CheckinController::class, 'manualScan'])->name('checkin.manual.scan');
        Route::get('/checkin/manual/live', [CheckinController::class, 'manualLive'])->name('checkin.manual.live');
        Route::get('/participants', [AdminParticipantController::class, 'index'])->name('participants.index');
        Route::post('/participants', [AdminParticipantController::class, 'store'])->name('participants.store');
        Route::delete('/participants/delete-selected', [AdminParticipantController::class, 'destroySelected'])->name('participants.destroy-selected');
        Route::get('/participants/template', [ParticipantImportController::class, 'template'])->name('participants.template');
        Route::post('/participants/import', [ParticipantImportController::class, 'store'])->name('participants.import');
        Route::get('/recipients/{categorySlug}', [AdminRecipientController::class, 'index'])->name('recipients.index');
        Route::get('/recipients/{categorySlug}/create', [AdminRecipientController::class, 'create'])->name('recipients.create');
        Route::get('/recipients/{categorySlug}/{recipient}/edit', [AdminRecipientController::class, 'edit'])->name('recipients.edit');
        Route::delete('/recipients/{categorySlug}/delete-selected', [AdminRecipientController::class, 'destroySelected'])->name('recipients.destroy-selected');
        Route::get('/recipients/{categorySlug}/template', [AdminRecipientController::class, 'template'])->name('recipients.template');
        Route::post('/recipients/{categorySlug}/import', [AdminRecipientController::class, 'import'])->name('recipients.import');
        Route::post('/recipients', [AdminRecipientController::class, 'store'])->name('recipients.store');
        Route::put('/recipients/{recipient}', [AdminRecipientController::class, 'update'])->name('recipients.update');
        Route::get('/categories', [AdminController::class, 'categories'])->name('categories.index');
        Route::get('/categories/create', [AdminController::class, 'createCategory'])->name('categories.create');
        Route::get('/categories/{category}/edit', [AdminController::class, 'editCategory'])->name('categories.edit');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    });
});
