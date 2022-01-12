<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\System\Config;

use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Button extends Field
{
    protected $_template = 'Gento_TangoTiendas::system/config/button.phtml';

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('tangotiendas/system/testToken');
    }

    public function getId()
    {
        return 'btn_tango_tiendas_token_test';
    }

    public function getButton()
    {
        return $this->getLayout()->createBlock(WidgetButton::class)
            ->setData([
                'id' => $this->getId(),
                'label' => __('Test token'),
            ]);
    }

    public function getButtonHtml()
    {
        return $this->getButton()->toHtml();
    }
}
