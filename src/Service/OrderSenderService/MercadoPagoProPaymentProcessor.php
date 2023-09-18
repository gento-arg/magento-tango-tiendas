<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Service\OrderSenderService;

use Gento\TangoTiendas\Api\OrderSenderService\PaymentMethodProcessorInterface;
use Gento\TangoTiendas\Service\ConfigService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;
use Psr\Log\LoggerInterface;
use TangoTiendas\Model\PaymentFactory;

class MercadoPagoProPaymentProcessor implements PaymentMethodProcessorInterface
{
    protected PaymentFactory $paymentFactory;
    protected DateTime $dateTime;
    protected ConfigService $configService;
    protected LoggerInterface $logger;

    /**
     * @param PaymentFactory $paymentFactory
     * @param DateTime $dateTime
     * @param ConfigService $configService
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentFactory $paymentFactory,
        DateTime $dateTime,
        ConfigService $configService,
        LoggerInterface $logger
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->dateTime = $dateTime;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(Order $order, OrderPaymentInterface $orderPayment)
    {
        if ($orderPayment->getMethod() !== ConfigCheckoutPro::METHOD) {
            return null;
        }
        $paymentModel = $this->paymentFactory->create();
        $additionalInfo = $orderPayment->getAdditionalInformation();
        $this->logger->info(sprintf('[MPPro] Order #%s additionalInfo', $order->getIncrementId()), $additionalInfo);

        if (!isset($additionalInfo['mp_status'])) {
            throw new LocalizedException(__('Invalid status'));
        }

        if ($additionalInfo['mp_status'] !== 'approved') {
            throw new LocalizedException(__('Payment not approved'));
        }

        if (!isset($additionalInfo['payment_0_total_amount'])) {
            throw new LocalizedException(__('Payment information not found'));
        }

        $amount = $additionalInfo['payment_0_total_amount'];
        $installments = $additionalInfo['payment_0_installments'];

        $installmentAmount = $amount / $installments;
        $paymentModel->setPaymentID($order->getEntityId())
            ->setVoucherNo($orderPayment->getEntityId())
            ->setTransactionDate($this->dateTime->gmtDate())
            ->setCardCode('DI')
            ->setCardPlanCode('1')
            ->setInstallments($installments)
            ->setInstallmentAmount($this->configService->round($installmentAmount))
            ->setPaymentTotal($this->configService->round($orderPayment->getAmountPaid()));
        return $paymentModel;
    }
}
