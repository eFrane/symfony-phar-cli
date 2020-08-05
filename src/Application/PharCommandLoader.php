<?php


namespace EFrane\PharTest\Application;


/**
 * Class CommandLoader
 * @package EFrane\PharTest\Util
 */
class PharCommandLoader
{
    /**
     * @var iterable
     */
    private $commands;

    public function __construct(iterable $commands)
    {
        $this->commands = $commands;
    }

    public function getCommands(): iterable
    {
        return $this->commands;
    }
}
