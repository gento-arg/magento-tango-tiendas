<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\System\Config;

use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 *
 */
class Button extends Field
{
    protected $_template = 'Gento_TangoTiendas::system/config/button.phtml';

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('tangotiendas/system/testToken');
    }

    /**
     * @throws LocalizedException
     * @return mixed
     */
    public function getButton()
    {
        return $this->getLayout()->createBlock(WidgetButton::class)
            ->setData([
                'id' => $this->getId(),
                'label' => __('Test token'),
            ]);
    }

    /**
     * @throws LocalizedException
     * @return mixed
     */
    public function getButtonHtml()
    {
        return $this->getButton()->toHtml();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'btn_tango_tiendas_token_test';
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
