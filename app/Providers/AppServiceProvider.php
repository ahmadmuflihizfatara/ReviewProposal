<?php

namespace App\Providers;

use App\Models\Proposal;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('review', fn (User $user) => $user->isReviewer());
        Gate::define('submit-proposal', fn (User $user) => $user->role === Role::Issuer);
        Gate::define('approve-kaprodi', fn (User $user) => $user->role === Role::Kaprodi);
        Gate::define('approve-sekretaris', fn (User $user) => $user->role === Role::Sekretaris);
        Gate::define('view-proposal', fn (User $user, Proposal $proposal) => $user->role !== Role::Issuer || $proposal->issuer_id === $user->id);
    }
}
