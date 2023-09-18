<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Queue\Sender;

use Exception;
use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterface;
use Gento\TangoTiendas\Logger\Logger;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderNotification implements QueueOrderSenderServiceInterface
{
    protected PublisherInterface $publisher;
    protected Logger $logger;

    /**
     * @param PublisherInterface $publisher
     * @param Logger $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        Logger $logger
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function sendOrder(OrderInterface $order)
    {
        $this->logger->info(__('Trying to queue order: %1', $order->getIncrementId()));
        try {
            $this->publisher->publish(self::TOPIC_NAME, $order->getId());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
