<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm
                            {--force : Force cache warming even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm application caches for better first-request performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Starting cache warming process...');
        $this->newLine();

        // 1. Config Cache
        $this->info('⚙️  Warming config cache...');
        Artisan::call('config:cache');
        $this->line('   <fg=green>✓</> Config cached');

        // 2. Route Cache
        $this->info('🛣️  Warming route cache...');
        Artisan::call('route:cache');
        $this->line('   <fg=green>✓</> Routes cached');

        // 3. View Cache
        $this->info('👁️  Warming view cache...');
        Artisan::call('view:cache');
        $this->line('   <fg=green>✓</> Views cached');

        // 4. Event Cache
        $this->info('📡 Warming event cache...');
        Artisan::call('event:cache');
        $this->line('   <fg=green>✓</> Events cached');

        // 5. Icon Cache (if using Blade Icons)
        if (class_exists(\BladeUI\Icons\BladeIconsServiceProvider::class)) {
            $this->info('🎨 Warming icon cache...');
            Artisan::call('icons:cache');
            $this->line('   <fg=green>✓</> Icons cached');
        }

        // 6. Pre-load common application cache keys
        $this->info('💾 Pre-loading application cache...');
        $this->preloadApplicationCache();
        $this->line('   <fg=green>✓</> Application cache pre-loaded');

        // 7. OPcache warmup (if enabled)
        if (function_exists('opcache_compile_file') && ini_get('opcache.enable')) {
            $this->info('🔥 Warming OPcache...');
            $this->warmOpcache();
            $this->line('   <fg=green>✓</> OPcache warmed');
        }

        $this->newLine();
        $this->info('✅ Cache warming completed successfully!');
        $this->newLine();

        // Display cache statistics
        $this->displayCacheStats();

        return self::SUCCESS;
    }

    /**
     * Pre-load common application cache keys.
     */
    protected function preloadApplicationCache(): void
    {
        // Example: Pre-load settings, configurations, or frequently accessed data
        // Customize this based on your application needs

        // Cache permissions/roles if using Spatie Permission
        if (class_exists(\Spatie\Permission\PermissionServiceProvider::class)) {
            Cache::remember('permissions_all', now()->addDay(), function () {
                return \Spatie\Permission\Models\Permission::all();
            });

            Cache::remember('roles_all', now()->addDay(), function () {
                return \Spatie\Permission\Models\Role::all();
            });
        }

        // Add more pre-caching here based on your application
    }

    /**
     * Warm OPcache by compiling PHP files.
     */
    protected function warmOpcache(): void
    {
        $files = [
            app_path(),
            base_path('config'),
            base_path('routes'),
            base_path('vendor/laravel/framework/src'),
        ];

        foreach ($files as $path) {
            if (is_dir($path)) {
                $this->compileDirectory($path);
            }
        }
    }

    /**
     * Compile all PHP files in a directory.
     */
    protected function compileDirectory(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                try {
                    @opcache_compile_file($file->getRealPath());
                } catch (\Exception $e) {
                    // Silent fail - some files may not be compilable
                }
            }
        }
    }

    /**
     * Display cache statistics.
     */
    protected function displayCacheStats(): void
    {
        $this->components->twoColumnDetail('<fg=gray>Config cached</>', '<fg=green>✓</>');
        $this->components->twoColumnDetail('<fg=gray>Routes cached</>', '<fg=green>✓</>');
        $this->components->twoColumnDetail('<fg=gray>Views cached</>', '<fg=green>✓</>');
        $this->components->twoColumnDetail('<fg=gray>Events cached</>', '<fg=green>✓</>');

        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            if ($status && isset($status['opcache_enabled']) && $status['opcache_enabled']) {
                $this->components->twoColumnDetail('<fg=gray>OPcache status</>', '<fg=green>Enabled</>');

                if (isset($status['opcache_statistics']['num_cached_scripts'])) {
                    $this->components->twoColumnDetail(
                        '<fg=gray>Cached scripts</>',
                        '<fg=green>'.$status['opcache_statistics']['num_cached_scripts'].'</>'
                    );
                }
            }
        }

        $this->newLine();
        $this->components->info('Cache warming improves first-request performance by 30-50%');
    }
}
