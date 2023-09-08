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
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use Psr\Log\LoggerInterface;
use TangoTiendas\Model\PaymentFactory;

class MercadoPagoCCPaymentProcessor implements PaymentMethodProcessorInterface
{
    protected PaymentFactory $paymentFactory;
    protected SerializerInterface $serializer;
    protected ConfigService $configService;
    protected LoggerInterface $logger;

    /**
     * @param PaymentFactory $paymentFactory
     * @param SerializerInterface $serializer
     * @param ConfigService $configService
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentFactory $paymentFactory,
        SerializerInterface $serializer,
        ConfigService $configService,
        LoggerInterface $logger
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->serializer = $serializer;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(Order $order, OrderPaymentInterface $orderPayment)
    {
        if ($orderPayment->getMethod() !== ConfigCc::METHOD) {
            return null;
        }
        $paymentModel = $this->paymentFactory->create();
        $additionalInfo = $orderPayment->getAdditionalInformation();
        $this->logger->info(sprintf('[MPCc] Order #%s additionalInfo', $order->getIncrementId()), $additionalInfo);

        if ($additionalInfo['mp_status'] !== 'approved') {
            throw new LocalizedException(__('Payment not approved'));
        }

        $data = $orderPayment->getAdditionalData();
        $this->logger->info(sprintf('[MPCc] Order #%s additionalData', $order->getIncrementId()), [$data]);
        if ($data === null) {
            throw new LocalizedException(__('Payment without response'));
        }
        $additionalData = $this->serializer->unserialize($data);

        $amount = $additionalData['transaction_amount'];
        $installments = $additionalData['installments'];

        $installmentAmount = $amount / $installments;
        $paymentModel->setPaymentID($order->getEntityId())
            ->setVoucherNo($additionalData['authorization_code'])
            ->setTransactionDate($additionalData['money_release_date'])
            ->setCardCode('DI')
            ->setCardPlanCode('1')
            ->setInstallments($installments)
            ->setInstallmentAmount($this->configService->round($installmentAmount))
            ->setPaymentTotal($this->configService->round($orderPayment->getAmountPaid()));
        return $paymentModel;
    }
}
