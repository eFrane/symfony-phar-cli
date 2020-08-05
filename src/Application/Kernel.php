<?php

namespace EFrane\PharTest\Application;

use EFrane\PharTest\CompilerPass\HidePharCommandsFromDefaultConsolePass;
use Phar;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use const DIRECTORY_SEPARATOR;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../../config/{packages}/*.yaml');
        $container->import('../../config/{packages}/'.$this->environment.'/*.yaml');

        if (is_file(\dirname(__DIR__).'/../config/services.yaml')) {
            $container->import('../../config/{services}.yaml');
            $container->import('../../config/{services}_'.$this->environment.'.yaml');
        } elseif (is_file($path = \dirname(__DIR__).'/config/services.php')) {
            (require $path)($container->withPath($path), $this);
        }
    }

    public function configureRoutes(RoutingConfigurator $routes)
    {
        // Do nothing, there are no routes
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

    protected function build(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addCompilerPass(new HidePharCommandsFromDefaultConsolePass());
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
}
