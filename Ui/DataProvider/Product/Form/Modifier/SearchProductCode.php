<?php

namespace Gento\TangoTiendas\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Container;

class SearchProductCode extends AbstractModifier
{
    /**
     * @var array
     */
    protected $meta = [];
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->addSearchProductCodeLink();

        return $this->meta;
    }

    private function addSearchProductCodeLink()
    {
        $tangoCodePath = $this->arrayManager->findPath('tango_sku', $this->meta, null, 'children');

        if ($tangoCodePath) {
            $this->meta = $this->arrayManager->merge(
                $tangoCodePath . '/arguments/data/config',
                $this->meta,
                ['additionalClasses' => 'admin__field-small']
            );

            $tangoSearchCodeButton['arguments']['data']['config'] = [
                'dataScope' => 'search_tango_code_button',
                'displayAsLink' => true,
                'formElement' => Container::NAME,
                'componentType' => Container::NAME,
                'component' => 'Magento_Ui/js/form/components/button',
                'template' => 'ui/form/components/button/container',
                'actions' => [
                    [
                        'targetName' => 'product_form.product_form.tango_code_search',
                        'actionName' => 'toggleModal',
                    ]
                ],
                'title' => __('Search tango code'),
                'additionalForGroup' => true,
                'provider' => false,
                'source' => 'product_details',
                'sortOrder' =>
                    $this->arrayManager->get($tangoCodePath . '/arguments/data/config/sortOrder', $this->meta) + 1,
            ];

            $this->meta = $this->arrayManager->set(
                $this->arrayManager->slicePath($tangoCodePath, 0, -1) . '/search_tango_code_button',
                $this->meta,
                $tangoSearchCodeButton
            );
        }

        return $this;
    }

}
