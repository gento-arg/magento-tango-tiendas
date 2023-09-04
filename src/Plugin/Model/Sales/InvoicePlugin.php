<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model\Sales;

use Gento\TangoTiendas\Service\OrderSenderService;
use Gento\TangoTiendas\Service\OrderSenderServiceFactory;
use Magento\Sales\Api\Data\InvoiceInterface;

class InvoicePlugin
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

    /**
     * @param InvoiceInterface $subject
     *
     * @return void
     */
    public function afterPay(
        InvoiceInterface $subject
    ) {
        $order = $subject->getOrder();

        /** @var OrderSenderService $orderSender */
        $orderSender = $this->orderSenderServiceFactory->create();
        $orderSender->sendOrder($order);
    }
}
