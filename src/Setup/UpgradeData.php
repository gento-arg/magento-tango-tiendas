<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $configsToMigrate = [
                'active',
                'api_token',
                'token_test',
                'store',
                'warehouse',
                'guest_id',
                'import_stock/enabled',
                'import_stock/frequency',
                'import_stock/time',
            ];

            foreach ($configsToMigrate as $configPath) {
                $before = 'sales_channels/gento_tangotiendas/' . $configPath;
                $after = 'tango/gento_tangotiendas/' . $configPath;

                $setup->getConnection()
                    ->update(
                        $setup->getTable('core_config_data'),
                        [
                            'path' => $after,
                        ],
                        [
                            $setup->getConnection()->quoteInto('path = ?', $before),
                        ]
                    );
            }
        }
        $setup->endSetup();
    }
}
