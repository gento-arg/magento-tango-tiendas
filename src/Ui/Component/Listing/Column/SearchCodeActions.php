<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class SearchCodeActions extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['sku_code'])) {
                    $item[$this->getData('name')] = [
                        'select' => [
                            'href' => '#',
                            'label' => __('Select'),
                            'callback' => [
                                [
                                    'provider' => 'product_form.product_form.tango_code_search.tango_search_code_grid',
                                    'target' => 'selectSku',
                                    'params' => $item['sku_code'],
                                ],
                                [
                                    'provider' => 'product_form.product_form.tango_code_search',
                                    'target' => 'actionDone',
                                ]
                            ],
                        ],
                    ];
                }
            }
        }
        return $dataSource;
    }
}
