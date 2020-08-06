<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Application;


use Phar;
use RuntimeException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;

class PharKernel extends Kernel
{
    protected function build(ContainerBuilder $containerBuilder)
    {
        // don't do anything special
    }

    protected function initializeContainer()
    {
        if (Phar::running(false)) {
            $this->loadPrebuiltContainer();

            return;
        }

        parent::initializeContainer();
    }

    private function prepareRuntimeDir(): string
    {
        $runtimeDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_phar_runtime';

        if (!is_dir($runtimeDir) && !mkdir($runtimeDir) && !is_dir($runtimeDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $runtimeDir));
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

    /**
     * @param bool $debug
     * @return ConfigCache
     */
    public function getConfigCache(bool $debug): ConfigCache
    {
        (new Filesystem())->mkdir('build/phar_container');

        $cache = new ConfigCache('build/phar_container/phar_container.php', $debug);

        return $cache;
    }

    public static function prebuildContainer(string $environment, bool $debug): void
    {
        if (Phar::running(false)) {
            throw new RuntimeException('Cannot prebuild container in running phar');
        }

        $kernel = new self($environment, $debug);
        $kernel->boot();

        $container = $kernel->buildContainer();
        $container->compile();

        $kernel->dumpContainer(
            $kernel->getConfigCache($debug),
            $container,
            $kernel->getContainerClass(),
            $kernel->getContainerBaseClass()
        );
    }

    protected function loadPrebuiltContainer(): void
    {
        $configCache = $this->getConfigCache($this->isDebug());
        $cachePath = $configCache->getPath();

        $errorLevel = error_reporting(E_ALL ^ E_WARNING);

        try {
            if (!is_file($cachePath) || !is_object($this->container = include $cachePath)) {
                throw new RuntimeException('Failed to load container');
            }

            $this->container->set('kernel', $this);

            error_reporting($errorLevel);

            return;
        } catch (\Throwable $e) {
        }
    }
}
