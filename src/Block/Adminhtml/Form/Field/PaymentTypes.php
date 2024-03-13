<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\Adminhtml\Form\Field;
/**
 * List of payment types
 */
class PaymentTypes extends AbstractField
{
    public const TYPE_CASH_PAYMENT = 'cashpayment';
    public const TYPE_PAYMENT = 'payment';
    /**
     * @var array
     */
    protected $_items = [
        ['id' => self::TYPE_CASH_PAYMENT, 'name' => 'Cash payment'],
        ['id' => self::TYPE_PAYMENT, 'name' => 'Payment method'],
    ];

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }
}
