<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\DeleteTask;

final class AttributeOptionPipelineFactory extends AbstractPipelineFactory
{
    public function create(): PipelineInterface
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));

        return $pipeline
            // AttributeOption
            ->pipe($this->taskProvider->get(RetrieveOptionsTask::class))
            ->pipe($this->taskProvider->get(CreateUpdateDeleteTask::class))
            // Option
            ->pipe($this->taskProvider->get(CreateUpdateTask::class))
            ->pipe($this->taskProvider->get(DeleteTask::class))
        ;
    }
}
