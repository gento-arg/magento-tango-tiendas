<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderPlugin
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * @param OrderInterface $subject
     * @param OrderItemInterface[] $items
     *
     * @return OrderItemInterface[][]
     */
    public function beforeSetItems(
        OrderInterface $subject,
        array $items
    ) {
        $orderItems = [];
        foreach ($items as $item) {
            if ($item->getProductType() == 'simple' || $item->getProductType() == 'virtual') {
                if (!isset($orderItems[$item->getSku()])) {
                    $orderItems[$item->getSku()] = [];
                }
                $orderItems[$item->getSku()][] = $item;
            } else if ($item->getProductType() == 'bundle') {
                foreach ($item->getChildrenItems() as $childItem) {
                    if ($childItem->getProductType() == 'configurable') {
                        if (!isset($orderItems[$childItem->getSku()])) {
                            $orderItems[$childItem->getSku()] = [];
                        }
                        $orderItems[$childItem->getSku()][] = $childItem;
                    }
                }
            }
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', array_keys($orderItems), 'in')
            ->create();

        $productList = $this->productRepository->getList($searchCriteria);

        foreach ($productList->getItems() as $product) {
            if (!$product->getTangoSku()) {
                continue;
            }

            foreach ($orderItems[$product->getSku()] as $orderItem) {
                $orderItem->setTangoSku($product->getTangoSku());
            }
        }

        return [$items];
    }
}
