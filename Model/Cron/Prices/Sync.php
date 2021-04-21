<?php
declare (strict_types=1);

namespace Gento\TangoTiendas\Model\Cron\Prices;

use Gento\TangoTiendas\Logger\Logger;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use TangoTiendas\Service\PricesFactory;

class Sync
{
    const CONFIG_TOKEN_PATH = 'sales_channels/gento_tangotiendas/api_token';
    const CONFIG_ACTIVE_PATH = 'sales_channels/gento_tangotiendas/active';
    const CONFIG_PRICES_ENABLE_PATH = 'sales_channels/gento_tangotiendas/import_prices/enabled';

    /**
     * @var PricesFactory
     */
    protected $pricesServiceFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        PricesFactory $pricesServiceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->pricesServiceFactory = $pricesServiceFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        $websites = array_map(function ($item) {
            return $item->getId();
        }, $this->storeManager->getWebsites());

        $tokens = [];
        foreach ($websites as $websiteId) {
            $isActive = (bool)$this->getConfig(static::CONFIG_ACTIVE_PATH, $websiteId);
            if (!$isActive) {
                continue;
            }

            $isEnable = (bool)$this->getConfig(static::CONFIG_PRICES_ENABLE_PATH, $websiteId);
            if (!$isEnable) {
                continue;
            }

            $token = $this->getConfig(static::CONFIG_TOKEN_PATH, $websiteId);
            if (!in_array($token, $tokens)) {
                $tokens[] = $token;
            }
        }

        $this->logger->info(__('Tokens finded: %1', count($tokens)));

        $response = [];
        $step = 1;
        foreach ($tokens as $token) {
            /** @var \TangoTiendas\Service\Stocks $service */
            $service = $this->pricesServiceFactory->create([
                'accessToken' => $token,
            ]);

            $this->logger->info(__('Proccesing token: %1', $this->getMaskedToken($token)));

            $updated = $proccesed = 0;
            try {
                /** @var \TangoTiendas\Model\PagingResult $data */
                $data = $service->getList();
                do {
                    /** @var \TangoTiendas\Model\Price $item */
                    foreach ($data->getData() as $item) {
                        $proccesed++;

                        $updated++;
                    }
                    if ($data->hasMoreData()) {
                        $data = $service->getList();
                    }
                } while ($data->hasMoreData());
            } catch (\Throwable $th) {
                $this->logger->critical($th->getMessage());
                $response[$step] = __('Error: %1', $th->getMessage());
            }

            if ($proccesed > 0) {
                $response[$step] = __('Processed/Updated: %1/%s', $proccesed, $updated);
            }
        }
        return $response;
    }

    private function getConfig($path, $websiteId)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    private function getMaskedToken($token)
    {
        return $token;
    }
}
