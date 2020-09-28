<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Command;


use EFrane\PharBuilder\Command\PharCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooCommand extends PharCommand
{
    public static $defaultName = 'foo';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello');

        return Command::SUCCESS;
    }
}
