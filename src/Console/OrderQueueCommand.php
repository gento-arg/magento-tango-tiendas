<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Console;

use Gento\TangoTiendas\Api\QueueOrderSenderServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Sales\Model\OrderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderQueueCommand extends Command
{
    /**
     * @var State
     */
    private $state;
    private QueueOrderSenderServiceInterface $senderService;
    private OrderRepository $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param State $state
     * @param QueueOrderSenderServiceInterface $senderService
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param string|null $name
     */
    public function __construct(
        State $state,
        QueueOrderSenderServiceInterface $senderService,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        string $name = null
    ) {
        parent::__construct($name);
        $this->state = $state;
        $this->senderService = $senderService;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('tangotiendas:order:queue')
            ->setDescription('Queue orders to process on cron.')
            ->addOption(
                'order_id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                __('Order Increment IDs')
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderIds = $input->getOption('order_id');

        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderIds, 'in')->create();

        try {
            $orders = $this->orderRepository->getList($searchCriteria);
            foreach ($orders->getItems() as $order) {
                $this->senderService->sendOrder($order);
            }
        } catch (\Exception $e) {
            $output->writeln(__('An error has occured: %1', $e->getMessage()));
            return 1;
        }
        return 0;
    }
}
