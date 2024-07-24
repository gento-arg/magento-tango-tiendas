<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model\Sales\Payment\CaptureCommand;

use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterface;
use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterfaceFactory;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;
use Gento\TangoTiendas\Api\Data\LoggerInterface;

class MercadoPagoCCPlugin
{
    /**
     * @var QueueOrderSenderServiceInterfaceFactory
     */
    protected $orderSenderServiceFactory;
    protected LoggerInterface $logger;

    /**
     * @param QueueOrderSenderServiceInterfaceFactory $orderSenderServiceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        QueueOrderSenderServiceInterfaceFactory $orderSenderServiceFactory,
        LoggerInterface $logger
    ) {
        $this->orderSenderServiceFactory = $orderSenderServiceFactory;
        $this->logger = $logger;
    }

    /**
     * @param CaptureCommand $subject
     * @param Phrase $result
     * @param OrderPaymentInterface $payment
     * @param float|string $amount
     * @param OrderInterface $order
     *
     * @return void
     */
    public function afterExecute(
        CaptureCommand $subject,
        Phrase $result,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        $this->logger->info(__(
            'After execute %1 %2 (%3) State: %4, Payment Method: %5',
            self::class,
            $order->getIncrementId(),
            $order->getId(),
            $order->getState(),
            $payment->getMethod()
        ));
        if ($order->getState() !== Order::STATE_PROCESSING) {
            return;
        }

        if ($payment->getMethod() !== ConfigCc::METHOD &&
            $payment->getMethod() !== ConfigCheckoutPro::METHOD) {
            return;
        }

        /** @var QueueOrderSenderServiceInterface $orderSender */
        $orderSender = $this->orderSenderServiceFactory->create();
        $orderSender->sendOrder($order);
    }
}
