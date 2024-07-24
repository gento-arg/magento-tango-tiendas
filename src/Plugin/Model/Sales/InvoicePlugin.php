<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model\Sales;

use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterface;
use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterfaceFactory;
use Magento\Sales\Api\Data\InvoiceInterface;

class InvoicePlugin
{
    /**
     * @var QueueOrderSenderServiceInterfaceFactory
     */
    protected $orderSenderServiceFactory;

    public function __construct(
        QueueOrderSenderServiceInterfaceFactory $orderSenderServiceFactory
    ) {
        $this->orderSenderServiceFactory = $orderSenderServiceFactory;
    }

    /**
     * @param InvoiceInterface $subject
     *
     * @return void
     */
    public function afterPay(
        InvoiceInterface $subject
    ) {
        $order = $subject->getOrder();

        /** @var QueueOrderSenderServiceInterface $orderSender */
        $orderSender = $this->orderSenderServiceFactory->create();
        $orderSender->sendOrder($order);
    }
}
