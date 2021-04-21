<?php

namespace Gento\TangoTiendas\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigService
{
    public const CONFIG_API_TOKEN = 'api_token';
    public const CONFIG_GUEST_ID = 'guest_id';

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

    public function getCustomerGuestId()
    {
        return $this->getConfig(self::CONFIG_GUEST_ID);
    }

    private function getConfig($path)
    {
        return $this->scopeConfig->getValue('tango/gento_tangotiendas/' . $path);
    }

}
