<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Payment\Helper\Data as paymentData;

class PaymentMethodsList extends AbstractField
{
    /**
     * @var paymentData
     */
    private $paymentHelper;

    /**
     * @param Context     $context
     * @param paymentData $paymentHelper
     * @param array       $data
     */
    public function __construct(
        Context $context,
        paymentData $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentHelper = $paymentHelper;
    }

    public function getItems()
    {
        $allPaymentMethodsArray = $this->paymentHelper->getPaymentMethodList();
        return array_map(function ($idx, $name) {
            return ['id' => $idx, 'name' => sprintf('%s (%s)', $name, $idx)];
        }, array_keys($allPaymentMethodsArray), array_values($allPaymentMethodsArray));
    }
}
