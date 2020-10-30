<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Cron\Stock;

use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TangoTiendas\Service\StocksFactory;

class Sync
{
    const CONFIG_TOKEN_PATH = 'sales_channels/gento_tangotiendas/api_token';
    const CONFIG_ACTIVE_PATH = 'sales_channels/gento_tangotiendas/active';
    const CONFIG_STOCK_ENABLE_PATH = 'sales_channels/gento_tangotiendas/import_stock/enabled';

    /**
     * @var StocksFactory
     */
    protected $stocksServiceFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var StockItemInterfaceFactory
     */
    protected $stockItemFactory;

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
        StocksFactory $stocksServiceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        StockItemInterfaceFactory $stockItemFactory,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->stocksServiceFactory = $stocksServiceFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->stockItemFactory = $stockItemFactory;
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
            $isActive = (bool) $this->getConfig(static::CONFIG_ACTIVE_PATH, $websiteId);
            if (!$isActive) {
                continue;
            }

            $isEnable = (bool) $this->getConfig(static::CONFIG_STOCK_ENABLE_PATH, $websiteId);
            if (!$isEnable) {
                continue;
            }

            $token = $this->getConfig(static::CONFIG_TOKEN_PATH, $websiteId);
            if (!in_array($token, $tokens)) {
                $tokens[] = $token;
            }
        }

        $response = [];
        $step = 1;
        foreach ($tokens as $token) {
            /** @var \TangoTiendas\Service\Stocks $service */
            $service = $this->stocksServiceFactory->create([
                'accessToken' => $token,
            ]);
            $updated = $proccesed = 0;
            try {
                /** @var \TangoTiendas\Model\PagingResult $data */
                $data = $service->getList();
                do {
                    /** @var \TangoTiendas\Model\Stock $item */
                    foreach ($data->getData() as $item) {
                        $proccesed++;
                        $searchCriteria = $this->searchCriteriaBuilder
                            ->addFilter('tango_sku', $item->getSKUCode())
                            ->create();

                        $productList = $this->productRepository->getList($searchCriteria);
                        if ($productList->getTotalCount() == 0) {
                            continue;
                        }

                        $producList = $productList->getItems();

                        $product = array_pop($producList);

                        $stockItem = $this->stockItemFactory->create();
                        $stockItem->setQty($item->getQuantity());

                        /** @var \Magento\Inventory\Model\Stock $productStock */
                        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
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
}
