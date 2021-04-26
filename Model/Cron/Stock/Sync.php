<?php
declare (strict_types=1);

namespace Gento\TangoTiendas\Model\Cron\Stock;

use Gento\TangoTiendas\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use TangoTiendas\Service\ProductsFactory;
use TangoTiendas\Service\StocksFactory;

class Sync
{
    const CONFIG_TOKEN_PATH = 'tango/gento_tangotiendas/api_token';
    const CONFIG_ACTIVE_PATH = 'tango/gento_tangotiendas/active';
    const CONFIG_STOCK_ENABLE_PATH = 'tango/gento_tangotiendas/import_stock/enabled';

    /**
     * @var StocksFactory
     */
    protected $stocksServiceFactory;

    /**
     * @var ProductsFactory
     */
    protected $productServiceFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    protected $productRepositoryFactory;

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
        ProductsFactory $productServiceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SourceItemInterfaceFactory $sourceItemFactory,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->stocksServiceFactory = $stocksServiceFactory;
        $this->productServiceFactory = $productServiceFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepositoryFactory = $productRepositoryFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
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

            $isEnable = (bool)$this->getConfig(static::CONFIG_STOCK_ENABLE_PATH, $websiteId);
            if (!$isEnable) {
                continue;
            }

            $token = $this->getConfig(static::CONFIG_TOKEN_PATH, $websiteId);
            if (!in_array($token, $tokens)) {
                $tokens[] = $token;
            }
        }

        $this->logger->info(__('Tokens finded: %1', count($tokens)));
        $productRepository = $this->productRepositoryFactory->create();

        $response = [];
        $step = 1;
        foreach ($tokens as $tokenNumber => $token) {
            $this->logger->info(__('Processing token %1', $tokenNumber + 1));

            /** @var \TangoTiendas\Service\Stocks $service */
            $service = $this->stocksServiceFactory->create([
                'accessToken' => $token,
            ]);

            /**
             * $kits = [
             *  'SKU of Kit' =>[
             *      'SKU Component 1' => 'Qty 1',
             *      'SKU Component 2' => 'Qty 2',
             *  ]
             * ]
             */
            $kits = $this->getParsedKits($token);

            $baseStock = [];

            $updated = $proccesed = 0;
            $page = 1;

            try {
                do {
                    /** @var \TangoTiendas\Model\PagingResult $data */
                    $data = $service->getList(500, $page++);

                    /** @var \TangoTiendas\Model\Stock $item */
                    foreach ($data->getData() as $item) {
                        $proccesed++;

                        $baseStock[$item->getSKUCode()] = $item->getQuantity();

                        $searchCriteria = $this->searchCriteriaBuilder
                            ->addFilter('tango_sku', $item->getSKUCode())
                            ->create();

                        $productList = $productRepository->getList($searchCriteria);
                        if ($productList->getTotalCount() == 0) {
                            $this->logger->info(__('Unknow sku: %1', $item->getSKUCode()));
                            continue;
                        }
                        if ($productList->getTotalCount() > 1) {
                            $this->logger->warning(__('Multiple products with sku: %1', $item->getSKUCode()));
                            continue;
                        }

                        $producList = $productList->getItems();

                        $product = array_pop($producList);

                        $sourceItem = $this->sourceItemFactory->create();
                        $sourceItem->setSourceCode('default');
                        $sourceItem->setSku($item->getSKUCode());
                        $sourceItem->setQuantity($item->getQuantity());
                        $sourceItem->setStatus((int)($item->getQuantity() > 0));
                        $this->sourceItemsSaveInterface->execute([$sourceItem]);

                        $this->logger->info(__('New stock sku: %1 %2', $product->getSku(), $item->getQuantity()));

                        $updated++;
                    }
                } while ($data->hasMoreData());
            } catch (\Throwable $th) {
                $this->logger->critical($th->getMessage());
                $response[$step] = __('Error: %1', $th->getMessage());
            }

            $kitsStock = [];
            foreach ($kits as $kitSku => $kitQtys) {
                $stock = null;

                // Eval every component for the current kit
                foreach ($kitQtys as $componentSku => $qtyRequired) {
                    if ($qtyRequired < 0) {
                        $this->logger->error(__('Invalid qty for kit %1', $kitSku));
                        break;
                    }

                    // If the component doesnt exist on baseStock will be 0 available
                    $componentQty = $baseStock[$componentSku] ?? 0;

                    // If the required qty is greater than the current stock will be 0 available
                    if ($qtyRequired > $componentQty) {
                        $stock = 0;
                        break;
                    }

                    // The real availability for this component will be stock / required
                    $componentQty = $componentQty / $qtyRequired;

                    // First time
                    if ($stock === null) {
                        $stock = $componentQty;
                    }

                    // The stock will be the min between every component stock
                    $stock = min($stock, $componentQty);
                }

                $kitsStock[$kitSku] = $stock;
            }
            unset($baseStock);

            foreach ($kitsStock as $kitSku => $qty) {
                $proccesed++;

                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('tango_sku', $kitSku)
                    ->create();

                $productList = $productRepository->getList($searchCriteria);
                if ($productList->getTotalCount() == 0) {
                    $this->logger->info(__('Unknow sku: %1', $kitSku));
                    continue;
                }

                if ($productList->getTotalCount() > 1) {
                    $this->logger->warning(__('Multiple products with sku: %1', $kitSku));
                    continue;
                }

                $producList = $productList->getItems();

                $product = array_pop($producList);

                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSourceCode('default');
                $sourceItem->setSku($product->getSku());
                $sourceItem->setQuantity($qty);
                $sourceItem->setStatus((int)($qty > 0));
                $this->sourceItemsSaveInterface->execute([$sourceItem]);
                $this->logger->info(__('New stock sku: %1 %2', $product->getSku(), $qty));

                $updated++;
            }

            if ($proccesed > 0) {
                $response[$step] = __('Processed/Updated: %1/%2', $proccesed, $updated);
                $this->logger->info($response[$step]);
            }
        }
        return $response;
    }

    private function getConfig($path, $websiteId)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    protected function getParsedKits($token)
    {
        /** @var \TangoTiendas\Service\Products $service */
        $service = $this->productServiceFactory->create([
            'accessToken' => $token,
        ]);

        $kits = [];
        $page = 1;
        do {
            /** @var \TangoTiendas\Model\PagingResult $result */
            $result = $service->getList(500, $page);
            foreach ($result->getData() as /** @var \TangoTiendas\Model\Product */ $kitItem) {
                if (!$kitItem->isKit()) {
                    continue;
                }
                array_map(function ($row) use (&$kits, $kitItem) {
                    $componentSku = $row->getComponentSKUCode();
                    $kitSku = $kitItem->getSKUCode();
                    $qty = $row->getQuantity();

                    if (!isset($kits[$kitSku])) {
                        $kits[$kitSku] = [];
                    }

                    $kits[$kitSku][$componentSku] = $qty;
                }, $kitItem->getProductComposition());
            }

            $page++;
        } while ($result->hasMoreData());

        return $kits;
    }

    private function getMaskedToken($token)
    {
        return $token;
    }
}
