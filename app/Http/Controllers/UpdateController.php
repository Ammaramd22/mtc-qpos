<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    private $migrationPassword = 'VsoftPOS2024!';

    /**
     * Run migrations via URL
     * URL: /run-migration?password=VsoftPOS2024!
     */
    public function runMigration(Request $request)
    {
        // Check password from query parameter
        $password = $request->get('password');
        if ($password !== $this->migrationPassword) {
            abort(403, 'Unauthorized. Invalid or missing password.');
        }

        try {
            Log::info('Running migrations via URL by user: ' . auth()->user()->id);

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = Artisan::output();

            // Clear caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            Log::info('Migrations completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Migration completed successfully',
                'migration_output' => $migrationOutput,
                'caches_cleared' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Migration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all caches via URL
     * URL: /clear-cache?password=VsoftPOS2024!
     */
    public function clearCache(Request $request)
    {
        // Check password from query parameter
        $password = $request->get('password');
        if ($password !== $this->migrationPassword) {
            abort(403, 'Unauthorized. Invalid or missing password.');
        }

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
