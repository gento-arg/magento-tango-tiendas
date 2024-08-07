<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Console;

use Gento\TangoTiendas\Model\Cron\Prices\Sync;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PricesImportCommand extends Command
{
    /**
     * @var Sync
     */
    protected $syncCommand;
    /**
     * @var State
     */
    private $state;

    /**
     * PricesImportCommand constructor.
     * @param Sync $syncCommand
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        Sync $syncCommand,
        State $state,
        string $name = null
    ) {
        $this->syncCommand = $syncCommand;
        parent::__construct($name);
        $this->state = $state;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('tangotiendas:prices:import');
        $this->setDescription('Import prices from TangoTiendas.');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $this->syncCommand->setOutput($output);
        $this->syncCommand->execute();
        return 0;
    }
}
