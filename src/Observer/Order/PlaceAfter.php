<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Observer\Order;

use Gento\TangoTiendas\Service\OrderSenderService;
use Gento\TangoTiendas\Service\OrderSenderServiceFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class PlaceAfter implements ObserverInterface
{
    /**
     * @var OrderSenderServiceFactory
     */
    protected $orderSenderServiceFactory;

    public function __construct(
        OrderSenderServiceFactory $orderSenderServiceFactory
    ) {
        $this->orderSenderServiceFactory = $orderSenderServiceFactory;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        // TODO: Validar si es necesario enviar actualizaciones
        if ($order->getCreatedAt() != $order->getUpdatedAt()) {
            return;
        }

        /** @var OrderSenderService $orderSender */
        $orderSender = $this->orderSenderServiceFactory->create();
        $orderSender->sendOrder($order);
    }
}
