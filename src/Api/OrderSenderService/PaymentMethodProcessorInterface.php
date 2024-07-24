<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Api\OrderSenderService;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use TangoTiendas\Model\CashPayment;
use TangoTiendas\Model\Payment;

interface PaymentMethodProcessorInterface
{
    /**
     * @param Order $order
     * @param OrderPaymentInterface $orderPayment
     *
     * @return Payment|CashPayment|null
     */
    public function process(Order $order, OrderPaymentInterface $orderPayment);
}
