<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model\Sales\Payment\CaptureCommand;

use Gento\TangoTiendas\Service\OrderSenderService;
use Gento\TangoTiendas\Service\OrderSenderServiceFactory;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;

class MercadoPagoCCPlugin
{
    /**
     * @var OrderSenderServiceFactory
     */
    protected $orderSenderServiceFactory;

    /**
     * @param OrderSenderServiceFactory $orderSenderServiceFactory
     */
    public function __construct(
        OrderSenderServiceFactory $orderSenderServiceFactory
    ) {
        $this->orderSenderServiceFactory = $orderSenderServiceFactory;
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
        if ($order->getState() !== Order::STATE_PROCESSING) {
            return;
        }

        if ($payment->getMethod() !== ConfigCc::METHOD &&
            $payment->getMethod() !== ConfigCheckoutPro::METHOD) {
            return;
        }

        /** @var OrderSenderService $orderSender */
        $orderSender = $this->orderSenderServiceFactory->create();
        $orderSender->sendOrder($order);
    }
}
