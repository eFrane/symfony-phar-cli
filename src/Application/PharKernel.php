<?php
/**
 * @copyright 2020
 * @author Stefan "eFrane" Graupner <stefan.graupner@gmail.com>
 */

namespace EFrane\PharTest\Application;


use EFrane\PharTest\CompilerPass\HideDefaultConsoleCommandsFromPharPass;
use RuntimeException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;

class PharKernel extends Kernel
{
    const PHAR_CONTAINER_CACHE_DIR = 'build/phar_container';

    /**
     * @var bool Is the Phar currently being built
     */
    private $inBuild = false;

    public static function prebuildContainer(string $environment, bool $debug): void
    {
        if (Util::inPhar()) {
            throw new RuntimeException('Cannot prebuild container in running phar');
        }

        $kernel = new static($environment, $debug);
        $kernel->boot();
        $kernel->setInBuild(true);

        $container = $kernel->buildContainer();
        $container->compile(true);

        $kernel->dumpContainer(
            $kernel->getConfigCache($debug),
            $container,
            $kernel->getContainerClass(),
            $kernel->getContainerBaseClass()
        );

        $containerFinder = Finder::create()
            ->in(self::PHAR_CONTAINER_CACHE_DIR)
            ->name('*ProdContainer.php')
            ->files();

        foreach ($containerFinder as $fileInfo) {
            $container = file_get_contents($fileInfo->getRealPath());

            $container = str_replace('include_once \dirname(__DIR__, 3).', 'include_once \'../../\'.', $container);

            file_put_contents($fileInfo->getRealPath(), $container);
        }
    }

    /**
     * @param bool $debug
     * @return ConfigCache
     */
    public function getConfigCache(bool $debug): ConfigCache
    {
        if ($this->isInBuild()) {
            // reset pre-built container during build
            $fs = new Filesystem();
            if ($fs->exists(self::PHAR_CONTAINER_CACHE_DIR)) {
                $fs->remove(glob(self::PHAR_CONTAINER_CACHE_DIR));
            }

            $fs->mkdir(self::PHAR_CONTAINER_CACHE_DIR);
        }

        $path = 'phar_container/phar_container.php';
        if (!Util::inPhar()) {
            $path = 'build/' . $path;
        }

        $cache = new ConfigCache($path, $debug);

        return $cache;
    }

    public function getCacheDir()
    {
        $runtimeDir = $this->prepareRuntimeDir();

        return $runtimeDir.DIRECTORY_SEPARATOR.'cache';
    }

    private function prepareRuntimeDir(): string
    {
        $runtimeDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_phar_runtime';

        if (!is_dir($runtimeDir) && !mkdir($runtimeDir) && !is_dir($runtimeDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $runtimeDir));
        }

        return $runtimeDir;
    }

    public function getLogDir()
    {
        $runtimeDir = $this->prepareRuntimeDir();

        return $runtimeDir.DIRECTORY_SEPARATOR.'log';
    }

    public function getProjectDir(): string
    {
        $projectDir = parent::getProjectDir();

        if ($this->isInBuild()) {
            return '.';
        }

        if (Util::inPhar()) {
            return Util::pharRoot();
        }

        return $projectDir;
    }

    /**
     * @return bool
     */
    public function isInBuild(): bool
    {
        return $this->inBuild;
    }

    /**
     * @param bool $inBuild
     */
    public function setInBuild(bool $inBuild): void
    {
        $this->inBuild = $inBuild;
    }

    protected function build(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addCompilerPass(new HideDefaultConsoleCommandsFromPharPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }

    protected function initializeContainer()
    {
        if (Util::inPhar()) {
            $this->loadPrebuiltContainer();

            return;
        }

        parent::initializeContainer();
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
