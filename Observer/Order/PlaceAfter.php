<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Observer\Order;

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
        /** @var Order $order  */
        $order = $observer->getEvent()->getOrder();

        // Solo se envia la primera vez
        // TODO: Validar si es necesario enviar actualizaciones
        if ($order->getCreatedAt() != $order->getUpdatedAt()) {
            return;
        }

        $orderSender = $this->orderSenderServiceFactory->create();
        $orderSender->sendOrder($order);
    }
}
