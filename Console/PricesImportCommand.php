<?php
declare (strict_types=1);

namespace Gento\TangoTiendas\Console;

use Gento\TangoTiendas\Model\Cron\Prices\Sync;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PricesImportCommand extends Command
{
    /**
     * @var Sync
     */
    protected $syncCommand;

    public function __construct(
        Sync $syncCommand,
        string $name = null
    ) {
        $this->syncCommand = $syncCommand;
        parent::__construct($name);
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
        $this->syncCommand->execute();
    }
}
