<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Utils\InstallBusinessModules;
use App\Utils\InstallUtil;
use Composer\Semver\Comparator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Abort;

//use Illuminate\Support\Facades\Storage;

class InstallController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $outputLog;

    protected $appVersion;

    protected $macActivationKeyChecker;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->appVersion = config('author.app_version');
        $this->env = config('app.env');

        //Check if mac based activation key is required or not.
        $this->macActivationKeyChecker = false;
        if (file_exists(__DIR__.'/MacActivationKeyChecker.php')) {
            include_once __DIR__.'/MacActivationKeyChecker.php';
            $this->macActivationKeyChecker = $mac_is_enabled;
        }

        $this->installSettings();
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        $this->ensureStorageDirs();
        config(['app.debug' => true]);
        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // ignore during first install
        }
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Ensure Laravel storage folders exist (prevents "valid cache path" / silent session loss).
     */
    private function ensureStorageDirs(): void
    {
        $dirs = [
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
            public_path('uploads'),
            public_path('uploads/business_logos'),
            public_path('uploads/documents'),
            public_path('uploads/img'),
            public_path('uploads/invoice_logos'),
            public_path('uploads/temp'),
        ];
        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (! is_file($dir.'/.gitkeep')) {
                @file_put_contents($dir.'/.gitkeep', '');
            }
        }
    }

    /**
     * Check if project is already installed then show 404 error
     */
    private function isInstalled()
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            abort(404);
        }
    }

    /**
     * This function deletes .env file.
     */
    private function deleteEnv()
    {
        $envPath = base_path('.env');
        if ($envPath && file_exists($envPath)) {
            unlink($envPath);
        }

        return true;
    }

    /**
     * Installation
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        return view('install.index');
    }

    public function checkServer()
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        $output = [];

        //Check for php version
        $output['php'] = (PHP_MAJOR_VERSION > 8 || (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION >= 1)) ? true : false;
        $output['php_version'] = PHP_VERSION;

        //Check for php extensions
        $output['openssl'] = extension_loaded('openssl') ? true : false;
        $output['pdo'] = extension_loaded('pdo') ? true : false;
        $output['mbstring'] = extension_loaded('mbstring') ? true : false;
        $output['tokenizer'] = extension_loaded('tokenizer') ? true : false;
        $output['xml'] = extension_loaded('xml') ? true : false;
        $output['curl'] = extension_loaded('curl') ? true : false;
        $output['zip'] = extension_loaded('zip') ? true : false;
        $output['gd'] = extension_loaded('gd') ? true : false;

        //Check for writable permission. storage and the bootstrap/cache directories should be writable by your web server
        $output['storage_writable'] = is_writable(storage_path());
        $output['cache_writable'] = is_writable(base_path('bootstrap/cache'));

        $output['next'] = $output['php'] && $output['openssl'] && $output['pdo'] && $output['mbstring'] && $output['tokenizer'] && $output['xml'] && $output['curl'] && $output['zip'] && $output['gd'] && $output['storage_writable'] && $output['cache_writable'];

        return view('install.check-server')
            ->with(compact('output'));
    }

    public function details()
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        //Check if .env.example is present or not.
        $env_example = base_path('.env.example');
        if (! file_exists($env_example)) {
            exit("<b>.env.example file not found in <code>$env_example</code></b> <br/><br/> - In the downloaded codebase you will find .env.example file, please upload it and refresh this page.");
        }

        $business_packs = InstallBusinessModules::packs();
        $selected_packs = old('business_packs', ['general']);

        return view('install.details')
            ->with('activation_key', $this->macActivationKeyChecker)
            ->with(compact('business_packs', 'selected_packs'));
    }

    public function postDetails(Request $request)
    {
        //Check for .env file
        $this->isInstalled();
        $this->installSettings();

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $request->validate(
                [
                    'APP_NAME' => 'required',
                    'DB_DATABASE' => 'required',
                    'DB_USERNAME' => 'required',
                    'DB_PASSWORD' => 'nullable',
                    'DB_HOST' => 'required',
                    'DB_PORT' => 'required',
                    'MAIL_FROM_ADDRESS' => 'nullable|email',
                ],
                [
                    'APP_NAME.required' => 'App Name is required',
                    'DB_DATABASE.required' => 'Database Name is required',
                    'DB_USERNAME.required' => 'Database Username is required',
                    'DB_HOST.required' => 'Database Host is required',
                    'DB_PORT.required' => 'Database port is required',
                ]
            );

            $this->outputLog = new BufferedOutput;

            $input = $request->only(['APP_NAME', 'APP_TITLE', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
                'MAIL_MAILER',
                'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_ENCRYPTION',
                'MAIL_USERNAME', 'MAIL_PASSWORD', ]);

            $input['DB_PASSWORD'] = $input['DB_PASSWORD'] ?? '';
            $input['APP_TITLE'] = $input['APP_TITLE'] ?: $input['APP_NAME'];
            $input['MAIL_MAILER'] = $input['MAIL_MAILER'] ?: 'sendmail';
            $input['MAIL_FROM_ADDRESS'] = $input['MAIL_FROM_ADDRESS'] ?: 'noreply@localhost';
            $input['MAIL_FROM_NAME'] = $input['MAIL_FROM_NAME'] ?: $input['APP_NAME'];
            $input['APP_DEBUG'] = 'false';
            $input['APP_URL'] = url('/');
            $input['APP_ENV'] = 'live';
            $input['SESSION_SECURE_COOKIE'] = (request()->isSecure() || str_starts_with(url('/'), 'https')) ? 'true' : 'false';

            // Check for database details
            $port = (int) ($input['DB_PORT'] ?: 3306);
            $mysql_link = @mysqli_connect(
                $input['DB_HOST'],
                $input['DB_USERNAME'],
                $input['DB_PASSWORD'],
                $input['DB_DATABASE'],
                $port
            );
            if (! $mysql_link) {
                $msg = '<b>ERROR</b>: Failed to connect to MySQL: '.mysqli_connect_error();
                $msg .= "<br/>Check Database Host, Port, Name, Username and Password.";

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $msg);
            }
            @mysqli_close($mysql_link);

            //Check for activation key
            if ($this->macActivationKeyChecker) {
                $licence_code = $request->get('MAC_LICENCE_CODE');
                $licence_valid = mac_verify_licence_code($licence_code);
                if (! $licence_valid) {
                    return redirect()->back()
                        ->with('error', 'Invalid Activation Licence Code!!')
                        ->withInput();
                }

                $input['MAC_LICENCE_CODE'] = $licence_code;
            }

            // Persist specialty business modules (Nutmeg, Mobile shop, …) before .env step.
            $packs = $request->input('business_packs', ['general']);
            if (! is_array($packs)) {
                $packs = ['general'];
            }
            InstallBusinessModules::savePreferences($packs);

            // Build .env from example
            $envPathExample = base_path('.env.example');
            $envPath = base_path('.env');
            if (! file_exists($envPathExample)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '.env.example is missing. Re-upload the installer package.');
            }

            $env_lines = file($envPathExample);
            foreach ($input as $index => $value) {
                foreach ($env_lines as $key => $line) {
                    if (strpos($line, $index.'=') === 0 || strpos($line, $index.' =') === 0) {
                        $env_lines[$key] = $index.'="'.str_replace('"', '\"', (string) $value).'"'.PHP_EOL;
                    }
                }
            }
            $envContent = implode('', $env_lines);

            // Always embed a fresh APP_KEY (avoids MissingAppKeyException after install)
            $generatedKey = 'base64:'.base64_encode(random_bytes(32));
            if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
                $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$generatedKey, $envContent, 1);
            } else {
                $envContent = 'APP_KEY='.$generatedKey."\n".$envContent;
            }

            // Try automatic .env write + migrate (preferred)
            $wrote = @file_put_contents($envPath, $envContent);
            if ($wrote !== false) {
                @chmod($envPath, 0644);
                try {
                    // Apply DB config in this request (env not reloaded yet)
                    config([
                        'database.default' => 'mysql',
                        'database.connections.mysql.host' => $input['DB_HOST'],
                        'database.connections.mysql.port' => $port,
                        'database.connections.mysql.database' => $input['DB_DATABASE'],
                        'database.connections.mysql.username' => $input['DB_USERNAME'],
                        'database.connections.mysql.password' => $input['DB_PASSWORD'],
                        'app.name' => $input['APP_NAME'],
                        'app.url' => $input['APP_URL'],
                        'app.key' => $generatedKey,
                    ]);
                    DB::purge('mysql');
                    DB::reconnect('mysql');

                    Artisan::call('config:clear');
                    $this->ensureAppKey();
                    $this->runArtisanCommands();

                    return redirect()->route('install.success');
                } catch (\Throwable $e) {
                    return view('install.envText')
                        ->with([
                            'envContent' => $envContent,
                            'envPath' => $envPath,
                            'autoError' => $e->getMessage(),
                        ]);
                }
            }

            // Could not write .env — show manual paste step
            return view('install.envText')
                ->with([
                    'envContent' => $envContent,
                    'envPath' => $envPath,
                    'autoError' => 'Could not write .env automatically. Create the file manually, then click Install Now.',
                ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Install error: '.$e->getMessage());
        }
    }

    //Generate key, migrate and seed
    private function runArtisanCommands()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();
        $this->ensureAppKey();

        DB::statement('SET default_storage_engine=INNODB;');
        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        //Artisan::call('storage:link');

        // Soft Owner features + default modules from install picker
        InstallBusinessModules::applyAfterInstall();
    }

    /**
     * Ensure .env has a valid APP_KEY (generate if missing/empty).
     */
    private function ensureAppKey(): string
    {
        $envPath = base_path('.env');
        $generatedKey = 'base64:'.base64_encode(random_bytes(32));

        if (is_file($envPath)) {
            $env = (string) file_get_contents($envPath);
            $needsKey = true;
            if (preg_match('/^APP_KEY=(.*)$/m', $env, $m)) {
                $current = trim($m[1], " \t\"'");
                if ($current !== '' && str_starts_with($current, 'base64:')) {
                    $needsKey = false;
                    $generatedKey = $current;
                }
            }
            if ($needsKey) {
                if (preg_match('/^APP_KEY=.*$/m', $env)) {
                    $env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$generatedKey, $env, 1);
                } else {
                    $env = 'APP_KEY='.$generatedKey."\n".$env;
                }
                @file_put_contents($envPath, $env);
            }
        }

        config(['app.key' => $generatedKey]);

        try {
            Artisan::call('key:generate', ['--force' => true]);
            // Re-read in case artisan rewrote it
            if (is_file($envPath) && preg_match('/^APP_KEY=(.*)$/m', (string) file_get_contents($envPath), $m2)) {
                $generatedKey = trim($m2[1], " \t\"'");
                config(['app.key' => $generatedKey]);
            }
        } catch (\Throwable $e) {
            // Direct write above is enough if artisan fails
        }

        return $generatedKey;
    }

    public function installAlternate(Request $request)
    {
        try {
            $this->installSettings();

            $envPath = base_path('.env');
            if (! file_exists($envPath)) {
                return redirect()->route('install.details')
                    ->with('error', 'Looks like you haven\'t created the .env file at '.$envPath);
            }

            $this->ensureAppKey();
            $this->runArtisanCommands();

            return redirect()->route('install.success');
        } catch (\Throwable $e) {
            return redirect()->route('install.details')
                ->with('error', 'Install failed: '.$e->getMessage());
        }
    }

    public function success()
    {
        $prefs = InstallBusinessModules::loadPreferences();
        $selected_pack_labels = [];
        if (! empty($prefs['packs']) && is_array($prefs['packs'])) {
            $packs = InstallBusinessModules::packs();
            foreach ($prefs['packs'] as $id) {
                if (! empty($packs[$id]['label'])) {
                    $selected_pack_labels[] = $packs[$id]['label'];
                }
            }
        }

        return view('install.success')
            ->with(compact('selected_pack_labels'));
    }

    public function updateConfirmation()
    {
        $installUtil = new installUtil();
        $db_version = $installUtil->getSystemInfo('db_version');

        if (Comparator::greaterThan($this->appVersion, $db_version)) {
            return view('install.update_confirmation');
        } else {
            // abort(404);
            exit("<b> Update already done to Version <code>".$db_version."</code></b>");
        }
    }

    //Updating
    public function update(Request $request)
    {
        //Check if db_version is same as app_verison then 404
        //If app_version > db_version - run update script.
        //Else there is some problem.

        $version = null;

        try {
            DB::beginTransaction();

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            //Static version value is passed for 1.2 version.
            if ($version == 1.2) {
                exit('Update not supported. Kindly install again.');
            } elseif (is_null($version)) {
                $installUtil = new installUtil();
                $db_version = $installUtil->getSystemInfo('db_version');

                //if($db_version < $this->appVersion){
                if (Comparator::greaterThan($this->appVersion, $db_version)) {
                    ini_set('max_execution_time', 0);
                    ini_set('memory_limit', '512M');
                    $this->installSettings();
                    DB::statement('SET default_storage_engine=INNODB;');
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('module:publish');
                    Artisan::call('passport:install', ['--force' => true]);

                    $installUtil->setSystemInfo('db_version', $this->appVersion);
                } else {
                    abort(404);
                }
            } else {
                abort(404);
            }

            @DB::commit();

            $output = ['success' => 1,
                'msg' => 'Updated Succesfully to version '.$this->appVersion.' !!',
            ];

            return redirect('login')->with('status', $output);
        } catch (Exception $e) {
            DB::rollBack();
            exit($e->getMessage());
        }
    }
}
