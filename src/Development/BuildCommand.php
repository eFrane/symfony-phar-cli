<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Development;


use EFrane\PharTest\Application\PharKernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';

    public function configure(): void
    {
        $this->setDescription('Build the phar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errorReporting = error_reporting();
        error_reporting(E_ALL | E_STRICT);

        $retVal = Command::SUCCESS;

        if (!file_exists('box.json')) {
            $output->writeln("Please make sure you're running bin/console from the repo root");
            return Command::FAILURE;
        }

        try {
            PharKernel::prebuildContainer('prod', false);

            $buildProcess = new Process(['vendor/bin/box', 'compile']);
            $buildProcess->setTimeout(0);
            $buildProcess->setTty(true);
            $buildProcess->mustRun();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            $retVal = Command::FAILURE;
        } finally {
            error_reporting($errorReporting);
        }

        return $retVal;
    }
}
