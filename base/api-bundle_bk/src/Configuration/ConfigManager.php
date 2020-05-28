<?php

namespace DomBase\DomApiBundle\Configuration;

use DomBase\DomApiBundle\Exception\UndefinedEntityException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class ConfigManager
{
    private const CACHE_KEY = 'domapi.processed_config';

    /** @var array */
    private $apiConfig;
    private $debug;
    private $propertyAccessor;
    private $cache;
    /** @var array */
    private $originalBackendConfig;
    /** @var ConfigPassInterface[] */
    private $configPasses;

    public function __construct(array $originalBackendConfig, bool $debug, PropertyAccessorInterface $propertyAccessor, CacheInterface $cache)
    {
        $this->originalBackendConfig = $originalBackendConfig;
        $this->debug = $debug;
        $this->propertyAccessor = $propertyAccessor;
        $this->cache = $cache;
    }

    /**
     * @param ConfigPassInterface $configPass
     */
    public function addConfigPass(ConfigPassInterface $configPass)
    {
        $this->configPasses[] = $configPass;
    }

    public function getBackendConfig(string $propertyPath = null)
    {
        $this->loadBackendConfig();

        if (empty($propertyPath)) {
            return $this->apiConfig;
        }

        // turns 'design.menu' into '[design][menu]', the format required by PropertyAccess
        $propertyPath = '['.\str_replace('.', '][', $propertyPath).']';

        return $this->propertyAccessor->getValue($this->apiConfig, $propertyPath);
    }

    public function getEntityConfig(string $entityName): array
    {
        $apiConfig = $this->getBackendConfig();
        if (!isset($apiConfig['entities'][$entityName])) {
            throw new UndefinedEntityException(['entity_name' => $entityName]);
        }

        return $apiConfig['entities'][$entityName];
    }

    public function getEntityConfigByClass(string $fqcn): ?array
    {
        $apiConfig = $this->getBackendConfig();
        foreach ($apiConfig['entities'] as $entityName => $entityConfig) {
            if ($entityConfig['class'] === $fqcn) {
                return $entityConfig;
            }
        }

        return null;
    }

    public function getActionConfig(string $entityName, string $view, string $action): array
    {
        try {
            $entityConfig = $this->getEntityConfig($entityName);
        } catch (\Exception $e) {
            $entityConfig = [];
        }

        return $entityConfig[$view]['actions'][$action] ?? [];
    }

    public function isActionEnabled(string $entityName, string $view, string $action): bool
    {
        $entityConfig = $this->getEntityConfig($entityName);

        return !\in_array($action, $entityConfig['disabled_actions'], true) && \array_key_exists($action, $entityConfig[$view]['actions']);
    }

    /**
     * It processes the given backend configuration to generate the fully
     * processed configuration used in the application.
     *
     * @param array $apiConfig
     *
     * @return array
     */
    private function doProcessConfig($apiConfig): array
    {
        foreach ($this->configPasses as $configPass) {
            $apiConfig = $configPass->process($apiConfig);
        }

        return $apiConfig;
    }

    private function loadBackendConfig(): array
    {
        if (null !== $this->apiConfig) {
            return $this->apiConfig;
        }

        if (true === $this->debug) {
            return $this->apiConfig = $this->doProcessConfig($this->originalBackendConfig);
        }

        return $this->apiConfig = $this->cache->get(self::CACHE_KEY, function () {
            return $this->doProcessConfig($this->originalBackendConfig);
        });
    }
}
