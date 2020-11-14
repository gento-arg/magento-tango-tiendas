<?php
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

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * @param OrderInterface  $subject
     * @param OrderItemInterface[] $items
     */
    public function beforeSetItems(
        OrderInterface $subject,
        array $items
    ) {
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[$item->getProductId()] = $item;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', array_keys($orderItems), 'in')
            ->create();

        $productList = $this->productRepository->getList($searchCriteria);

        foreach ($productList->getItems() as $product) {
            if (!$product->getTangoSku()) {
                continue;
            }

            $orderItem = $orderItems[$product->getId()];
            $orderItem->setTangoSku($product->getTangoSku());
        }

        return [$items];
    }
}
