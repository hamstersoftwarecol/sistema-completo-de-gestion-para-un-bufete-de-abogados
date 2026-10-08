<?php

namespace App\Providers;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\LegalCase;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Gastos pendientes que el usuario puede aprobar (indicador del menú). */
    private function pendingApprovals(User $user): int
    {
        if ($user->isJunior()) {
            return 0;
        }

        return Expense::query()
            ->where('status', ExpenseStatus::Pending->value)
            ->when(! $user->isSuperadmin(), fn ($q) => $q
                ->where('user_id', '!=', $user->id)
                ->whereHas('legalCase', fn ($c) => $c->where('lawyer_id', $user->id)))
            ->count();
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');

        // El superadministrador puede hacer todo.
        Gate::before(fn (User $user) => $user->isSuperadmin() ? true : null);
        Gate::define('admin', fn (User $user) => $user->isSuperadmin());

        Route::model('case', LegalCase::class);

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User && ! session()->has('impersonator_id')) {
                $event->user->forceFill([
                    'last_login_at' => now(),
                    'last_login_ip' => request()->ip(),
                ])->saveQuietly();
            }
        });

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $view->with([
                'unreadNotifications' => $user->unreadNotifications()->latest()->limit(8)->get(),
                'unreadNotificationsCount' => $user->unreadNotifications()->count(),
                'unreadChatCount' => Message::query()->where('receiver_id', $user->id)->whereNull('read_at')->count(),
                'pendingApprovals' => $this->pendingApprovals($user),
            ]);
        });
    }
}
