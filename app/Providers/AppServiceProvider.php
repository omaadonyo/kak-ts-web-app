<?php

namespace App\Providers;

use App\Models\BookService;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureGates(): void
    {
        Gate::define('book-service', fn ($user) => $user->isClient());
        Gate::define('assign-booking', fn ($user) => $user->isAdmin());
        Gate::define('assess-booking', fn ($user) => $user->isTechnician() || $user->isAdmin());
        Gate::define('generate-quotation', fn ($user) => $user->isTechnician() || $user->isAdmin());
        Gate::define('accept-quotation', fn ($user, Quotation $q) => $user->isClient() && $q->bookService->user_id === $user->id);
        Gate::define('approve-project', fn ($user, Project $p) => $user->isClient() && $p->bookService->user_id === $user->id);
        Gate::define('mark-project-complete', fn ($user) => $user->isTechnician() || $user->isAdmin());
        Gate::define('view-assigned', fn ($user, BookService $b) => $user->isAdmin() || ($user->isTechnician() && $b->assigned_to === $user->id));
        Gate::define('record-payment', fn ($user, Invoice $i) => $user->isClient() && $i->bookService->user_id === $user->id);
        Gate::define('manage-users', fn ($user) => $user->isAdmin());
    }
}
