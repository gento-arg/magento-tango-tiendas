<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Cron\Prices;

use Gento\TangoTiendas\Logger\Logger;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Cron\Model\Schedule;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Group;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Output\OutputInterface;
use TangoTiendas\Model\PagingResult;
use TangoTiendas\Model\Price;
use TangoTiendas\Model\PriceList;
use TangoTiendas\Service\PriceLists;
use TangoTiendas\Service\PriceListsFactory;
use TangoTiendas\Service\Prices;
use TangoTiendas\Service\PricesFactory;

class Sync
{
    const CONFIG_ACTIVE_PATH = 'tango/gento_tangotiendas/active';
    const CONFIG_PRICES_ENABLE_PATH = 'tango/gento_tangotiendas/import_prices/enabled';
    const CONFIG_TOKEN_PATH = 'tango/gento_tangotiendas/api_token';
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
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    protected ProductRepositoryInterfaceFactory $productRepositoryFactory;
    /**
     * @var ProductTierPriceInterfaceFactory
     */
    private $productTierPriceInterfaceFactory;
    /**
     * @var ProgressBarFactory
     */
    private $barFactory;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var ProgressBar
     */
    private $currentBar;

    /**
     * Sync constructor.
     *
     * @param PricesFactory $pricesServiceFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManager
     * @param GroupRepositoryInterface $groupRepository
     * @param ProductRepositoryInterfaceFactory $productRepositoryFactory
     * @param Logger $logger
     * @param ProductTierPriceInterfaceFactory $productTierPriceInterfaceFactory
     * @param ProgressBarFactory $barFactory
     */
    public function __construct(
        PricesFactory $pricesServiceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        GroupRepositoryInterface $groupRepository,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        Logger $logger,
        ProductTierPriceInterfaceFactory $productTierPriceInterfaceFactory,
        ProgressBarFactory $barFactory
    ) {
        $this->pricesServiceFactory = $pricesServiceFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->productRepositoryFactory = $productRepositoryFactory;
        $this->logger = $logger;
        $this->productTierPriceInterfaceFactory = $productTierPriceInterfaceFactory;
        $this->barFactory = $barFactory;
    }

    public function execute(Schedule $schedule = null)
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

            $isEnable = (bool) $this->getConfig(static::CONFIG_PRICES_ENABLE_PATH, $websiteId);
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

        $errors = $response = [];
        $step = 1;
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('tango_id', null, 'neq')
            ->create();

        $customerGroups = $this->groupRepository->getList($searchCriteria);

        $matchesGroups = [];
        $defaultPriceList = null;
        $this->startProgress($customerGroups->getTotalCount(), 'Preparing groups');
        foreach ($customerGroups->getItems() as $customerGroup) {
            $this->advanceProgress();
            $tangoId = $customerGroup->getExtensionAttributes()->getTangoId();
            if ($customerGroup->getId() == Group::NOT_LOGGED_IN_ID) {
                $defaultPriceList = $tangoId;
            }
            if (!isset($matchesGroups[$tangoId])) {
                $matchesGroups[$tangoId] = [];
            }
            $matchesGroups[$tangoId][] = $customerGroup->getId();
        }
        $this->finishProgress();

        foreach ($tokens as $idxToken => $token) {
            $this->printOutput(sprintf(
                '<info>Token %s/%s</info>',
                $idxToken + 1,
                count($tokens)
            ));
            /** @var Prices $service */
            $service = $this->pricesServiceFactory->create([
                'accessToken' => $token,
            ]);

            $updated = $processed = 0;
            $page = 1;

            try {
                do {
                    /** @var PagingResult $data */
                    $data = $service->getList(500, $page++);
                    $this->startProgress(count($data->getData()), 'Preparing data page ' . ($page - 1));

                    $prices = [];
                    /** @var Price $item */
                    foreach ($data->getData() as $item) {
                        $this->advanceProgress();
                        $skuCode = $item->getSKUCode();
                        if (!isset($prices[$skuCode])) {
                            $prices[$skuCode] = [];
                        }
                        $prices[$skuCode][$item->getPriceListNumber()] = $item->getPrice();
                    }
                    $this->finishProgress();

                    $this->startProgress(count($prices), 'Processing prices');
                    foreach ($prices as $skuCode => $priceLists) {
                        $this->advanceProgress();
                        $processed++;

                        $reformatSkuCode = trim($skuCode);

                        $searchCriteria = $this->searchCriteriaBuilder
                            ->addFilter('tango_sku', $reformatSkuCode, 'like')
                            ->create();

                        $productList = $productRepository->getList($searchCriteria);
                        if ($productList->getTotalCount() == 0) {
                            $this->logger->info(__('Unknown sku: %1', $skuCode));
                            continue;
                        }
                        if ($productList->getTotalCount() > 1) {
                            $errors[] = __('Multiple products with sku: %1', $skuCode);
                            $this->logger->warning(__('Multiple products with sku: %1', $skuCode));
                            continue;
                        }

                        $producList = $productList->getItems();

                        $product = array_pop($producList);

                        $tiersHasChange = $hasChange = false;
                        $tierPrices = $product->getTierPrices();
                        $groupPrices = [];

                        foreach ($tierPrices as $tierPrice) {
                            $groupPrices[$tierPrice->getCustomerGroupId()] = [
                                'customer_group_id' => $tierPrice->getCustomerGroupId(),
                                'value' => $tierPrice->getValue(),
                                'qty' => $tierPrice->getQty()
                            ];
                        }
                        foreach ($priceLists as $priceListId => $price) {
                            if ($defaultPriceList == $priceListId) {
                                // Set default price
                                if ($price != $product->getPrice()) {
                                    $product->setPrice($price);
                                    $hasChange = true;
                                }
                            }

                            if ($defaultPriceList != $priceListId && isset($matchesGroups[$priceListId])) {
                                // Set group price
                                foreach ($matchesGroups[$priceListId] as $groupId) {
                                    if (isset($groupPrices[$groupId]) &&
                                        isset($groupPrices[$groupId]['value']) &&
                                        $groupPrices[$groupId]['value'] == $price) {
                                        continue;
                                    }

                                    $groupPrices[$groupId] = [
                                        'customer_group_id' => $groupId,
                                        'value' => $price,
                                        'qty' => 1
                                    ];
                                    $tiersHasChange = true;
                                }
                            }
                        }

                        if ($tiersHasChange) {
                            $productTierPrices = [];
                            foreach ($groupPrices as $price) {
                                $productTierPrices[] = $this->productTierPriceInterfaceFactory->create(
                                    [
                                        'data' => $price
                                    ]
                                );
                            }
                            $product->setTierPrices($productTierPrices);
                        }

                        try {
                            if ($hasChange || $tiersHasChange) {
                                $product->setStoreId(0);
                                $productRepository->save($product);
                            }
                            $updated++;
                        } catch (\Exception $th) {
                            $this->logger->critical($skuCode . ' ' . $th->getMessage());
                            $response[$step] = __('Error: %1 %2', $skuCode, $th->getMessage());
                            $errors[] = __('Error: %1 %2', $skuCode, $th->getMessage());
                        }
                    }
                    $this->finishProgress();
                } while ($data->hasMoreData());
            } catch (\Exception $th) {
                $this->logger->critical($th->getMessage());
                $response[$step] = __('Error: %1', $th->getMessage());
                $errors[] = __('Error: %1', $th->getMessage());
            }

            if ($processed > 0) {
                $response[$step] = __('Processed/Updated: %1/%s', $processed, $updated);
            }
        }

        if (count($errors) > 0 && $schedule === null) {
            throw new \Exception(implode("\n", $errors));
        }

        if (count($errors) > 0 && $schedule !== null) {
            $schedule->setMessages(implode("\n", $errors));
        }

        return $response;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    private function getConfig($path, $websiteId)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    private function getMaskedToken($token)
    {
        return $token;
    }

    private function printOutput($message)
    {
        if ($this->output != null) {
            $this->output->writeln($message);
        }
    }

    private function startProgress($max, $message)
    {
        if ($this->output != null) {
            $this->currentBar = $this->barFactory->create([
                'output' => $this->output,
                'max' => $max
            ]);
            $this->currentBar->setFormat(
                '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
            );
            $this->currentBar->setMessage($message . '...');
            $this->currentBar->start();
            $this->currentBar->display();
        }
    }

    private function advanceProgress()
    {
        if ($this->currentBar != null) {
            $this->currentBar->advance();
        }
    }

    private function finishProgress()
    {
        if ($this->currentBar != null) {
            $this->currentBar->finish();
        }
        if ($this->output != null) {
            $this->output->writeln('');
        }
    }
}
