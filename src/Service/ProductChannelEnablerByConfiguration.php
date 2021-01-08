<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ChannelConfiguration;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelConfigurationRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;

final class ProductChannelEnablerByConfiguration implements ProductChannelEnablerInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository */
    private $channelRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ChannelConfigurationRepository */
    private $channelConfigurationRepository;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ChannelRepository $channelRepository,
        ChannelConfigurationRepository $channelConfigurationRepository,
        LoggerInterface $akeneoLogger,
        EntityManagerInterface $entityManager
    ) {
        $this->channelRepository = $channelRepository;
        $this->channelConfigurationRepository = $channelConfigurationRepository;
        $this->logger = $akeneoLogger;
        $this->entityManager = $entityManager;
    }

    public function enableChannelForProduct(ProductInterface $product, array $resource, string $akeneoChannel): void
    {
        try {
            $channelConfigurations = $this->channelConfigurationRepository->findBy([
                'akeneoChannelCode' => $akeneoChannel
            ]);

            $this->entityManager->beginTransaction();
            //Disable the product for all channels
            $product->getChannels()->clear();

            foreach ($channelConfigurations as $channelConfiguration) {

                if(!$channelConfiguration instanceof ChannelConfiguration){
                    $this->logger->warning(\sprintf(
                        'Broken channel configuration found for product "%s".',
                        $product->getCode()
                    ));

                    continue;
                }
                $channelConfigurationChannel = $channelConfiguration->getSyliusChannel();
                if (!$channelConfigurationChannel instanceof ChannelInterface) {
                    $this->logger->warning(\sprintf(
                        'Channel not found for channel configuration "%s"',
                        $channelConfiguration->getId()
                    ));

                    continue;
                }

                $channel = $this->channelRepository->find($channelConfigurationChannel->getId());
                if (!$channel instanceof ChannelInterface) {
                    $this->logger->warning(\sprintf(
                        'Channel "%s" could not be activated for product "%s" because the channel was not found in the database.',
                        $channelConfigurationChannel->getId(),
                        $product->getCode()
                    ));

                    continue;
                }

                $product->addChannel($channel);
                $this->logger->info(\sprintf(
                    'Enabled channel "%s" for product "%s"',
                    $channel->getCode(),
                    $product->getCode()
                ));
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            throw $throwable;
        }
    }
}
