<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Queue;

use Gento\TangoTiendas\Api\OrderSenderServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;

class OrderSender
{
    protected OrderSenderServiceInterface $orderSenderService;
    protected OrderRepository $orderRepository;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        OrderSenderServiceInterface $orderSenderService,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderSenderService = $orderSenderService;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $incrementId
     *
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @return void
     */
    public function process(string $incrementId)
    {
        $order = $this->getOrder($incrementId);
        $this->orderSenderService->sendOrder($order);
        $this->orderRepository->save($order);
    }

    /**
     * @param string $incrementId
     *
     * @return OrderInterface|null
     */
    private function getOrder(string $incrementId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::INCREMENT_ID, $incrementId)
            ->create();
        $orders = $this->orderRepository->getList($criteria)->getItems();
        return count($orders) ? array_shift($orders) : null;
    }

}
