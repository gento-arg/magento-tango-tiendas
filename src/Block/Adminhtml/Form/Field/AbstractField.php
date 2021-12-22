<?php

declare(strict_types = 1);

namespace Gento\TangoTiendas\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

abstract class AbstractField extends Select
{
    /**
     * @inheirtdoc
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getItems() as $item) {
                $this->addOption($item['id'], addslashes($item['name']), $this->makeDataParams($item));
            }
        }
        return parent::_toHtml();
    }

    /**
     * @param $params
     *
     * @return array|array[]
     */
    public function makeDataParams($params)
    {
        return array_map(function ($k, $v) {
            return ['data-' . $k => $v];
        }, array_keys($params), $params);
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

}
