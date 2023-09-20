<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Config\Backend\Prices;

use Gento\TangoTiendas\Model\Config\Backend\AbstractCron;

class Cron extends AbstractCron
{
    const CRON_STRING_PATH = 'crontab/gento_tangotiendas/jobs/gento_tangotiendas_prices/schedule/cron_expr';
    const CRON_TIME_PATH = 'groups/gento_tangotiendas/groups/import_prices/fields/time/value';
    const CRON_FREQUENCY_PATH = 'groups/gento_tangotiendas/groups/import_prices/fields/frequency/value';
}
