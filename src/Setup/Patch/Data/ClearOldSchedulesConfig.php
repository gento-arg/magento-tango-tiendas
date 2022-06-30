<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ClearOldSchedulesConfig implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private WriterInterface $writer;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface          $writer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $writer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->writer = $writer;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->writer->delete('crontab/gento_tangotiendas/jobs/gento_tangotiendas_stock/schedule/cron_expr');
        $this->writer->delete('crontab/gento_tangotiendas/jobs/gento_tangotiendas_prices/schedule/cron_expr');

        $this->moduleDataSetup->endSetup();
    }
}
