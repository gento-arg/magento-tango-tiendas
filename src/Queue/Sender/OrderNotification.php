<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Queue\Sender;

use Exception;
use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Gento\TangoTiendas\Api\Data\LoggerInterface;

class OrderNotification implements QueueOrderSenderServiceInterface
{
    protected PublisherInterface $publisher;
    protected LoggerInterface $logger;

    /**
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function sendOrder(OrderInterface $order)
    {
        $this->logger->info(__(
            'Trying to queue order: %1 (%2)',
            $order->getIncrementId(),
            $order->getId()
        ));
        try {
            $this->publisher->publish(self::TOPIC_NAME, $order->getIncrementId());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
    }
}
