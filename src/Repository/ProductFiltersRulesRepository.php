<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;

final class ProductFiltersRulesRepository extends EntityRepository
{
    public function findOneByChannel(string $channelCode): ?ProductFiltersRules
    {
        $productFilterRules = $this->createQueryBuilder('pfr')
            ->where('pfr.channel = :channel')
            ->setParameter('channel', $channelCode)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;

        if (isset($productFilterRules[0]) && $productFilterRules[0] instanceof ProductFiltersRules) {
            return $productFilterRules[0];
        }

        return null;
    }
}
