<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Queue;

use Gento\TangoTiendas\Api\OrderSenderServiceInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class OrderSender
{
    protected OrderSenderServiceInterface $orderSenderService;
    protected OrderRepository $orderRepository;

    public function __construct(
        OrderSenderServiceInterface $orderSenderService,
        OrderRepository $orderRepository
    ) {
        $this->orderSenderService = $orderSenderService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $orderId
     *
     * @throws InputException
     * @throws NoSuchEntityException
     * @return void
     */
    public function process(string $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $this->orderSenderService->sendOrder($order);
    }
}
