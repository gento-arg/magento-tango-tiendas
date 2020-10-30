<?php

namespace Gento\TangoTiendas\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigService
{
    public const CONFIG_API_TOKEN = 'api_token';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getApiToken()
    {
        return trim($this->getConfig(self::CONFIG_API_TOKEN));
    }

    private function getConfig($path)
    {
        return $this->scopeConfig->getValue('sales_channels/gento_tangotiendas/' . $path);
    }
}
