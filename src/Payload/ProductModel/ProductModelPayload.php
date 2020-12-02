<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\ProductModel;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductModelPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_product_models';

    public const SELECT_PAGINATION_SIZE = 100;

    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $resources;

    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $modelResources;

    /** @var string */
    private $akeneoChannel;

    public function __construct(AkeneoPimClientInterface $akeneoPimClient, string $akeneoChannel)
    {
        parent::__construct($akeneoPimClient);

        $this->akeneoChannel = $akeneoChannel;
    }

    public function getResources(): ?ResourceCursorInterface
    {
        return $this->resources;
    }

    public function setResources(ResourceCursorInterface $resources): void
    {
        $this->resources = $resources;
    }

    public function getModelResources(): ?ResourceCursorInterface
    {
        return $this->modelResources;
    }

    public function setModelResources(?ResourceCursorInterface $modelResources): self
    {
        $this->modelResources = $modelResources;

        return $this;
    }

    public function getAkeneoChannel(): string
    {
        return $this->akeneoChannel;
    }
}
