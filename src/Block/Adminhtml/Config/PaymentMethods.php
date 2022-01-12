<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\Adminhtml\Config;

use Gento\TangoTiendas\Block\Adminhtml\Form\Field\PaymentMethodsList;
use Gento\TangoTiendas\Block\Adminhtml\Form\Field\PaymentTypes;
use Gento\TangoTiendas\Block\Adminhtml\Form\Field\TangoPayments;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class PaymentMethods extends AbstractFieldArray
{
    /**
     * @var TangoPayments
     */
    protected $_tangoSelectRenderer;

    /**
     * @var PaymentMethodsList
     */
    protected $_magentoSelectRenderer;

    /**
     * @var PaymentTypes
     */
    protected $_paymentTypeRenderer;

    /**
     * @param DataObject $row
     *
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $optionExtraAttr = [];
        $hash = $this->getTangoSelectRenderer()->calcOptionHash($row->getData('code'));
        $optionExtraAttr['option_' . $hash] = 'selected="selected"';

        $hash = $this->getMagentoSelectRenderer()->calcOptionHash($row->getData('payment'));
        $optionExtraAttr['option_' . $hash] = 'selected="selected"';

        $hash = $this->getPaymentTypesRenderer()->calcOptionHash($row->getData('type'));
        $optionExtraAttr['option_' . $hash] = 'selected="selected"';
        $row->setData('option_extra_attrs', $optionExtraAttr);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'code',
            [
                'label' => __('Tango Payment'),
                'renderer' => $this->getTangoSelectRenderer()
            ]);
        $this->addColumn(
            'payment',
            [
                'label' => __('Magento Payment'),
                'renderer' => $this->getMagentoSelectRenderer()
            ]);
        $this->addColumn(
            'type',
            [
                'label' => __('Type'),
                'renderer' => $this->getPaymentTypesRenderer()
            ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Payment Map');
    }

    /**
     * @throws LocalizedException
     * @return BlockInterface|PaymentMethodsList
     */
    protected function getMagentoSelectRenderer()
    {
        if (!$this->_magentoSelectRenderer) {
            $this->_magentoSelectRenderer = $this->getLayout()
                ->createBlock(
                    PaymentMethodsList::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            $this->_magentoSelectRenderer->setClass('admin__control-select');
        }
        return $this->_magentoSelectRenderer;
    }

    /**
     * @throws LocalizedException
     * @return BlockInterface|PaymentTypes
     */
    protected function getPaymentTypesRenderer()
    {
        if (!$this->_paymentTypeRenderer) {
            $this->_paymentTypeRenderer = $this->getLayout()
                ->createBlock(
                    PaymentTypes::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            $this->_paymentTypeRenderer->setClass('admin__control-select');
        }
        return $this->_paymentTypeRenderer;
    }

    /**
     * @throws LocalizedException
     * @return BlockInterface|TangoPayments
     */
    protected function getTangoSelectRenderer()
    {
        if (!$this->_tangoSelectRenderer) {
            $this->_tangoSelectRenderer = $this->getLayout()
                ->createBlock(
                    TangoPayments::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            $this->_tangoSelectRenderer->setClass('admin__control-select');
        }
        return $this->_tangoSelectRenderer;
    }

}
