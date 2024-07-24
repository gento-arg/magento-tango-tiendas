<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use TangoTiendas\Exceptions\ModelException;
use TangoTiendas\Model\CashPayment;
use TangoTiendas\Model\Order as TangoOrder;
use TangoTiendas\Model\Payment;

interface OrderSenderServiceInterface
{
    /**
     * @param OrderInterface $order
     *
     * @return void
     */
    public function sendOrder(OrderInterface $order);

    /**
     * @param Order $order
     *
     * @throws ModelException|LocalizedException
     * @return CashPayment|Payment
     */
    public function getPaymentModel(Order $order);

    /**
     * @param TangoOrder $orderModel
     * @param Item $orderItem
     *
     * @throws ModelException
     * @return void
     */
    public function addOrderItem(TangoOrder $orderModel, Item $orderItem);
}
