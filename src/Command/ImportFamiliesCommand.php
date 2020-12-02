<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\FamilyPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;

final class ImportFamiliesCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Families from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:families';

    /** @var FamilyPipelineFactory */
    private $familyPipelineFactory;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        FamilyPipelineFactory $familyPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->familyPipelineFactory = $familyPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
    }

    protected function configure(): void
    {
        $this->setDescription(self::DESCRIPTION);
        $this->addOption('channels', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Akeneo channel code');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $channels = $input->getOption('channels');
        if (!is_array($channels) || 0 === count($channels)) {
            $channelsApi = $this->clientFactory->createFromApiCredentials()->getChannelApi()->all();
            $channels = [];
            foreach ($channelsApi as $channelApi) {
                $channels[$channelApi['code']] = $channelApi['code'];
            }
        }

        $this->logger->notice(self::$defaultName);
        /** @var Pipeline $familyPipeline */
        $familyPipeline = $this->familyPipelineFactory->create();

        foreach ($channels as $channel) {
            $productModelPayload = new ProductModelPayload($this->clientFactory->createFromApiCredentials(), $channel);
            $familyPipeline->process($productModelPayload);
        }

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
