<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Application;


use Phar;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PharKernel extends Kernel
{
    protected function build(ContainerBuilder $containerBuilder)
    {
        // don't do anything special
    }

    protected function initializeContainer()
    {
        if (Phar::running(false)) {
            $container = $this->buildContainer();
            $container->compile();

            $this->container = $container;
            $this->container->set('kernel', $this);

            return;
        }

        parent::initializeContainer();
    }

    private function prepareRuntimeDir(): string
    {
        $runtimeDir = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_phar_runtime';
        if (!\is_dir($runtimeDir) && !mkdir($runtimeDir) && !is_dir($runtimeDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $runtimeDir));
        }

        return $runtimeDir;
    }

    public function getCacheDir()
    {
        $runtimeDir = $this->prepareRuntimeDir();

        return $runtimeDir . DIRECTORY_SEPARATOR . 'cache';
    }

    public function getLogDir()
    {
        $runtimeDir = $this->prepareRuntimeDir();

        return $runtimeDir . DIRECTORY_SEPARATOR . 'log';
    }

    public function getProjectDir(): string
    {
        $projectDir = parent::getProjectDir();

        if (Phar::running(false)) {
            $projectDir = '';
        }

        return $projectDir;
    }
}
