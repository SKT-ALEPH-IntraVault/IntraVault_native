<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
class AppServiceProvider extends ServiceProvider {
    public function register(): void { $this->app->singleton(\App\Services\Lab::class); }
    public function boot(): void {
        Paginator::useBootstrapFive();
        if ($this->app->environment('production')) \Illuminate\Support\Facades\URL::forceScheme('https');
        $connection=config('database.connections.mariadb');
        $connection['database'].='_lab';
        config(['database.connections.lab_write'=>$connection]);
        $connection['username']=env('LAB_DB_USERNAME','intravault_reader');
        $connection['password']=env('LAB_DB_PASSWORD','training-reader-local-only');
        $connection['options']=[\PDO::ATTR_EMULATE_PREPARES=>false,\PDO::MYSQL_ATTR_MULTI_STATEMENTS=>false];
        config(['database.connections.lab_read'=>$connection]);
    }
}
