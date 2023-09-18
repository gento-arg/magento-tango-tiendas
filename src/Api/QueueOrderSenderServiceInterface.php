<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface QueueOrderSenderServiceInterface
{
    public const TOPIC_NAME = 'tangotiendas.orders';

    /**
     * @param OrderInterface $order
     *
     * @return void
     */
    public function sendOrder(OrderInterface $order);
}
