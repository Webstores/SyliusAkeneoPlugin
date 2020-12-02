<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

final class ChannelConfigurationRepository extends EntityRepository
{


    public function findAllByAkeneoCodes(array $codes)
    {
        return $this->createQueryBuilder('c')
            ->where('c.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
            ;
    }
}
