<?php

namespace Gento\TangoTiendas\View\Element\UiComponent\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as UiDataProvider;
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
     * SearchCode constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param ProductsFactory $productsFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param array $meta
     * @param array $data
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
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
        $this->productsFactory = $productsFactory;
        $this->scopeConfig = $scopeConfigInterface;
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
        try {
            $data = $productsService->getList($pageSize, 1, $queryFilter)->getData();
            $totalCount = min(count($data), $pageSize);
        } catch (\Throwable $e) {
        }

        return [
            'totalRecords' => $totalCount,
            'items' => array_map(function ($item) {
                return [
                    'sku_code' => $item->getSKUCode(),
                    'description' => $item->getDescription(),
                ];
            }, $data)
        ];
    }
}
