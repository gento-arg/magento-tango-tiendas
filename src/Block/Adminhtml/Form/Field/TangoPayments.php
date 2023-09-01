<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\Adminhtml\Form\Field;

class TangoPayments extends AbstractField
{
    /**
     * @var array
     */
    protected $_items = [
        ['id' => 'A01', 'name' => 'Forma de cobro Web API 01'],
        ['id' => 'A02', 'name' => 'Forma de cobro Web API 02'],
        ['id' => 'A03', 'name' => 'Forma de cobro Web API 03'],
        ['id' => 'A04', 'name' => 'Forma de cobro Web API 04'],
        ['id' => 'A05', 'name' => 'Forma de cobro Web API 05'],
        ['id' => 'A06', 'name' => 'Forma de cobro Web API 06'],
        ['id' => 'A07', 'name' => 'Forma de cobro Web API 07'],
        ['id' => 'A08', 'name' => 'Forma de cobro Web API 08'],
        ['id' => 'A09', 'name' => 'Forma de cobro Web API 09'],
        ['id' => 'A10', 'name' => 'Forma de cobro Web API 10'],
        ['id' => 'MPA', 'name' => 'MercadoPago Argentina'],
        ['id' => 'MPANew', 'name' => 'MercadoPago Argentina (Adb Payment CC)'],
        ['id' => 'MPAPro', 'name' => 'MercadoPago Argentina (CheckoutPro)'],
        ['id' => 'PPA', 'name' => 'PayPal Argentina'],
        ['id' => 'PUA', 'name' => 'PayU Argentina'],
        ['id' => 'TPA', 'name' => 'Todo Pago Argentina'],
    ];

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }
}
