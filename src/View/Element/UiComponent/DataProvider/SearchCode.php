<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\View\Element\UiComponent\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as UiDataProvider;
use Psr\Log\LoggerInterface;
use TangoTiendas\Service\ProductsFactory;

class SearchCode extends UiDataProvider
{
    /**
     * @var ProductsFactory
     */
    private $productsFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string                $name
     * @param string                $primaryFieldName
     * @param string                $requestFieldName
     * @param ReportingInterface    $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface      $request
     * @param FilterBuilder         $filterBuilder
     * @param ProductsFactory       $productsFactory
     * @param ScopeConfigInterface  $scopeConfigInterface
     * @param LoggerInterface       $logger
     * @param array                 $meta
     * @param array                 $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        ProductsFactory $productsFactory,
        ScopeConfigInterface $scopeConfigInterface,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
        $this->productsFactory = $productsFactory;
        $this->scopeConfig = $scopeConfigInterface;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $token = $this->scopeConfig->getValue('tango/gento_tangotiendas/api_token');

        $productsService = $this->productsFactory->create([
            'accessToken' => $token,
        ]);

        $pageSize = $this->getSearchCriteria()->getPageSize();
//        $pageNumber = $this->getSearchCriteria()->getCurrentPage();
        $queryFilter = null;
        foreach ($this->getSearchCriteria()->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() == 'name') {
                    $queryFilter = $filter->getValue();
                }
            }
        }

        $data = [];
        $totalCount = 0;
        try {
            $data = $productsService->getList($pageSize, 1, $queryFilter)->getData();
            $totalCount = min(count($data), $pageSize);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return [
            'totalRecords' => $totalCount,
            'items' => array_map(function ($item) {
                /** @var \TangoTiendas\Model\Product $item */
                return [
                    'sku_code' => $item->getSKUCode(),
                    'description' => $item->getDescription(),
                    'additional_description' => $item->getAdditionalDescription(),
                ];
            }, $data)
        ];
    }
}
