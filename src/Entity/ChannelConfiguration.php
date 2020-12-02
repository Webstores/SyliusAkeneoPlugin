<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_channel")
 */
class ChannelConfiguration implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="akeneoChannelCode")
     */
    private $akeneoChannelCode;

    /**
     * @var ChannelInterface|null
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Channel\Model\Channel")
     * @ORM\JoinColumn(referencedColumnName="id", fieldName="syliusChannelId", nullable=false, name="syliusChannelId")
     */
    protected $syliusChannel;

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getAkeneoChannelCode(): ?string
    {
        return $this->akeneoChannelCode;
    }

    /**
     * @param string $akeneoChannelCode
     */
    public function setAkeneoChannelCode(string $akeneoChannelCode): void
    {
        $this->akeneoChannelCode = $akeneoChannelCode;
    }

    /**
     * @return ChannelInterface|null
     */
    public function getSyliusChannel(): ?ChannelInterface
    {
        return $this->syliusChannel;
    }

    /**
     * @param ChannelInterface $syliusChannel
     */
    public function setSyliusChannel(ChannelInterface $syliusChannel): void
    {
        $this->syliusChannel = $syliusChannel;
    }

}
