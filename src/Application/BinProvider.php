<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Application;

use Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Dotenv\Dotenv;

class BinProvider
{
    public static function runPharBin(): int
    {
        return (new self())();
    }

    public function __invoke(): int {
        if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
            echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
        }

        set_time_limit(0);

        $input = new ArgvInput();

        $workingDirectory = (string)$input->getParameterOption(['--cwd', '-C'], getcwd(), true);
        $debug = (bool)$input->getParameterOption(['--debug'], false, true);

        if (is_dir($workingDirectory)) {
            chdir($workingDirectory);
        } else {
            echo 'Error: Requested working directory '.$workingDirectory.' does not exist'.PHP_EOL;
            return 1;
        }

        putenv('APP_ENV=prod');
        putenv('APP_DEBUG='.($debug) ? '1' : 0);

        (new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');

        $kernel = new PharKernel('prod', $debug);
        $application = new PharApplication($kernel);

        try {
            return $application->run($input);
        } catch (Exception $e) {
            return 1;
        }
    }
}
