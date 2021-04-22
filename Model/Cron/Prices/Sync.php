<?php
declare (strict_types=1);

namespace Gento\TangoTiendas\Model\Cron\Prices;

use Gento\TangoTiendas\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use TangoTiendas\Service\PriceListsFactory;
use TangoTiendas\Service\PricesFactory;

class Sync
{
    const CONFIG_TOKEN_PATH = 'tango/gento_tangotiendas/api_token';
    const CONFIG_ACTIVE_PATH = 'tango/gento_tangotiendas/active';
    const CONFIG_PRICES_ENABLE_PATH = 'tango/gento_tangotiendas/import_prices/enabled';

    /**
     * @var PricesFactory
     */
    protected $pricesServiceFactory;

    /**
     * @var PriceListsFactory
     */
    protected $pricesListServiceFactory;

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
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        PricesFactory $pricesServiceFactory,
        PriceListsFactory $pricesListServiceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        GroupRepositoryInterface $groupRepository,
        productRepositoryInterfaceFactory $productRepositoryFactory,
        Logger $logger
    ) {
        $this->pricesServiceFactory = $pricesServiceFactory;
        $this->pricesListServiceFactory = $pricesListServiceFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->productRepositoryFactory = $productRepositoryFactory;
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
        $productRepository = $this->productRepositoryFactory->create();

        $response = [];
        $step = 1;
        foreach ($tokens as $token) {
            /** @var \TangoTiendas\Service\Prices $service */
            $service = $this->pricesServiceFactory->create([
                'accessToken' => $token,
            ]);
            /** @var \TangoTiendas\Service\PriceLists $service */
            $listService = $this->pricesListServiceFactory->create([
                'accessToken' => $token,
            ]);

            $priceLists = [];

            /** @var \TangoTiendas\Model\PagingResult $data */
            $data = $listService->getList();
            do {
                /** @var \TangoTiendas\Model\PriceList $item */
                foreach ($data->getData() as $item) {
                    $priceLists[] = $item;
                }
                if ($data->hasMoreData()) {
                    $data = $service->getList();
                }
            } while ($data->hasMoreData());

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('tango_id', null, 'neq')
                ->create();

            $customerGroups = $this->groupRepository->getList($searchCriteria);

            $matchesGroups = [];
            foreach ($customerGroups->getItems() as $customerGroup) {
                $tangoId = $customerGroup->getExtensionAttributes()->getTangoId();

                if (!isset($matchesGroups[$tangoId])) {
                    $matchesGroups[$tangoId] = [];
                }
                $matchesGroups[$tangoId][] = $customerGroup->getId();
            }

            $this->logger->info(__('Proccesing token: %1', $this->getMaskedToken($token)));

            $updated = $proccesed = 0;
            $page = 1;

            try {
                do {
                    /** @var \TangoTiendas\Model\PagingResult $data */
                    $data = $service->getList(500, $page++);

                    /** @var \TangoTiendas\Model\Price $item */
                    foreach ($data->getData() as $item) {
                        $proccesed++;

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

                        // TODO Process product price

                        $updated++;
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
