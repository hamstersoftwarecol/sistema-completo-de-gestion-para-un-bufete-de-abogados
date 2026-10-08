<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CaseNoteController;
use App\Http\Controllers\CasePartyController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientCommunicationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HearingController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/branding/logo', [BrandingController::class, 'logo'])->name('branding.logo');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/buscar', SearchController::class)->name('search');

    // Clientes + vista 360 + comunicaciones
    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/communications', [ClientCommunicationController::class, 'store'])->name('clients.communications.store');
    Route::post('clients/{client}/email', [ClientCommunicationController::class, 'email'])->name('clients.email');
    Route::delete('communications/{communication}', [ClientCommunicationController::class, 'destroy'])->name('communications.destroy');

    // Casos (expedientes)
    Route::get('cases/check-number', [LegalCaseController::class, 'checkNumber'])->name('cases.check-number');
    Route::get('cases/{case}/print', [LegalCaseController::class, 'print'])->name('cases.print');
    Route::resource('cases', LegalCaseController::class);

    Route::post('cases/{case}/parties', [CasePartyController::class, 'store'])->name('cases.parties.store');
    Route::put('parties/{party}', [CasePartyController::class, 'update'])->name('parties.update');
    Route::delete('parties/{party}', [CasePartyController::class, 'destroy'])->name('parties.destroy');

    Route::post('cases/{case}/notes', [CaseNoteController::class, 'store'])->name('cases.notes.store');
    Route::put('notes/{note}', [CaseNoteController::class, 'update'])->name('notes.update');
    Route::patch('notes/{note}/pin', [CaseNoteController::class, 'pin'])->name('notes.pin');
    Route::delete('notes/{note}', [CaseNoteController::class, 'destroy'])->name('notes.destroy');

    Route::post('cases/{case}/documents', [DocumentController::class, 'store'])->name('cases.documents.store');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Calendario judicial, audiencias y citas
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
    Route::resource('hearings', HearingController::class)->except('show');
    Route::resource('appointments', AppointmentController::class)->except('show');
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'status'])->name('appointments.status');

    // Pagos y recibos
    Route::resource('payments', PaymentController::class);

    // Gastos con aprobación / reembolso
    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
    Route::post('expenses/{expense}/reimburse', [ExpenseController::class, 'reimburse'])->name('expenses.reimburse');
    Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

    // Chat interno
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/unread', [ChatController::class, 'unread'])->name('chat.unread');
    Route::get('chat/{user}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('chat/{user}/messages', [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:60,1');

    // Asistente de IA
    Route::get('ai', [AiChatController::class, 'index'])->name('ai.index');
    Route::post('ai', [AiChatController::class, 'store'])->name('ai.store');
    Route::post('ai/message', [AiChatController::class, 'message'])->name('ai.message')->middleware('throttle:30,1');
    Route::put('ai/instructions', [AiChatController::class, 'instructions'])->name('ai.instructions');
    Route::get('ai/{conversation}', [AiChatController::class, 'show'])->whereNumber('conversation')->name('ai.show');
    Route::patch('ai/{conversation}', [AiChatController::class, 'rename'])->whereNumber('conversation')->name('ai.rename');
    Route::delete('ai/{conversation}', [AiChatController::class, 'destroy'])->whereNumber('conversation')->name('ai.destroy');

    // Notificaciones
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('notifications/read', [NotificationController::class, 'clear'])->name('notifications.clear');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');

    // Administración (sólo superadministrador)
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

        Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::post('case-types', [MasterDataController::class, 'storeType'])->name('case-types.store');
        Route::put('case-types/{type}', [MasterDataController::class, 'updateType'])->name('case-types.update');
        Route::delete('case-types/{type}', [MasterDataController::class, 'destroyType'])->name('case-types.destroy');
        Route::post('case-statuses', [MasterDataController::class, 'storeStatus'])->name('case-statuses.store');
        Route::put('case-statuses/{status}', [MasterDataController::class, 'updateStatus'])->name('case-statuses.update');
        Route::delete('case-statuses/{status}', [MasterDataController::class, 'destroyStatus'])->name('case-statuses.destroy');
        Route::post('courts', [MasterDataController::class, 'storeCourt'])->name('courts.store');
        Route::put('courts/{court}', [MasterDataController::class, 'updateCourt'])->name('courts.update');
        Route::delete('courts/{court}', [MasterDataController::class, 'destroyCourt'])->name('courts.destroy');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
        Route::post('settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance');
        Route::put('settings/ai', [SettingsController::class, 'updateAi'])->name('settings.ai');
        Route::post('settings/ai/test', [SettingsController::class, 'testAi'])->name('settings.ai.test');

        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{name}', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('backups/{name}', [BackupController::class, 'destroy'])->name('backups.destroy');
    });
});

require __DIR__.'/auth.php';
