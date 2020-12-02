<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Filter;

use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Enum\ProductFilterStatusEnum;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleSimpleType;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

final class ProductFilter
{
    private const AT_LEAST_COMPLETE = 'AT LEAST COMPLETE';

    private const ALL_COMPLETE = 'ALL COMPLETE';

    private const FULL_COMPLETE = 100;

    private const API_DATETIME_FORMAT = 'Y-m-d H:i:s';

    private const AVAILABLE_PRODUCT_MODEL_QUERIES = [
        'updated',
        'completeness',
        'categories',
        'family',
        'created',
    ];

    /** @var ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(ProductFiltersRulesRepository $productFiltersRulesRepository, SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider)
    {
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    public function getProductModelFilters(string $akeneoChannel): array
    {
        /** @var ProductFiltersRules $productFilterRules */
        $productFilterRules = $this->productFiltersRulesRepository->findOneByChannel($akeneoChannel);
        if (!$productFilterRules instanceof ProductFiltersRules) {
            return [];
        }

        $queryParameters = [];
        if ($productFilterRules->getMode() === ProductFilterRuleSimpleType::MODE) {
            $queryParameters = new SearchBuilder();

            $queryParameters = $this->getUpdatedFilter($productFilterRules, $queryParameters);

            $completeness = self::AT_LEAST_COMPLETE;
            if ($productFilterRules->getCompletenessValue() === self::FULL_COMPLETE) {
                $completeness = self::ALL_COMPLETE;
            }
            $this->getCompletenessFilter($productFilterRules, $queryParameters, $completeness);

            $queryParameters = $this->getExcludeFamiliesFilter($productFilterRules, $queryParameters);
            $queryParameters = $queryParameters->getFilters();
            $queryParameters = ['search' => $queryParameters, 'scope' => $productFilterRules->getChannel()];
        }

        if ($productFilterRules->getMode() === ProductFilterRuleAdvancedType::MODE && !empty($productFilterRules->getAdvancedFilter())) {
            return $this->getAdvancedFilter($productFilterRules, true);
        }

        return $queryParameters;
    }

    public function getProductFilters(string $akeneoChannel): array
    {
        /** @var ProductFiltersRules $productFilterRules */
        $productFilterRules = $this->productFiltersRulesRepository->findOneByChannel($akeneoChannel);
        if (!$productFilterRules instanceof ProductFiltersRules) {
            return [];
        }

        $queryParameters = [];
        if ($productFilterRules->getMode() === ProductFilterRuleSimpleType::MODE) {
            $queryParameters = new SearchBuilder();

            $queryParameters = $this->getUpdatedFilter($productFilterRules, $queryParameters);

            $this->getCompletenessFilter(
                $productFilterRules,
                $queryParameters,
                $productFilterRules->getCompletenessType(),
                $productFilterRules->getCompletenessValue()
            );
            $queryParameters = $this->getExcludeFamiliesFilter($productFilterRules, $queryParameters);
            $queryParameters = $this->getStatus($productFilterRules, $queryParameters);
            $queryParameters = $queryParameters->getFilters();
            $queryParameters = ['search' => $queryParameters, 'scope' => $productFilterRules->getChannel()];
        }

        if ($productFilterRules->getMode() === ProductFilterRuleAdvancedType::MODE && !empty($productFilterRules->getAdvancedFilter())) {
            return $this->getAdvancedFilter($productFilterRules);
        }

        return $queryParameters;
    }

    private function getStatus(ProductFiltersRules $productFilterRules, SearchBuilder $queryParameters): SearchBuilder
    {
        $status = $productFilterRules->getStatus();
        if ($status === ProductFilterStatusEnum::NO_CONDITION) {
            return $queryParameters;
        }

        return $queryParameters->addFilter(
            'enabled',
            Operator::EQUAL,
            $status === ProductFilterStatusEnum::ENABLED
        );
    }

    private function getAdvancedFilter(ProductFiltersRules $productFilterRules, bool $isProductModelFilter = false): array
    {
        if ($productFilterRules->getAdvancedFilter() === null) {
            return [];
        }

        parse_str($productFilterRules->getAdvancedFilter(), $advancedFilter);
        if (!array_key_exists('search', $advancedFilter)) {
            return $advancedFilter;
        }

        $advancedFilter['search'] = json_decode($advancedFilter['search'], true);
        if ($isProductModelFilter === true) {
            return $this->getProductModelAdvancedFilter($advancedFilter);
        }

        return $advancedFilter;
    }

    private function getProductModelCompletenessTypeAdvancedFilter(array $filter): array
    {
        $filter['search']['completeness'][0]['operator'] = self::AT_LEAST_COMPLETE;
        if ($filter['search']['completeness'][0]['value'] === self::FULL_COMPLETE) {
            $filter['search']['completeness'][0]['operator'] = self::ALL_COMPLETE;
        }
        unset($filter['search']['completeness'][0]['value']);

        return $filter;
    }

    private function getProductModelAdvancedFilter(array $advancedFilter): array
    {
        $advancedFilter['search'] = array_filter($advancedFilter['search'], static function (string $key): bool {
            return in_array($key, self::AVAILABLE_PRODUCT_MODEL_QUERIES);
        }, \ARRAY_FILTER_USE_KEY);

        if (array_key_exists('completeness', $advancedFilter['search']) && is_array($advancedFilter['search']['completeness'])) {
            $advancedFilter = $this->getProductModelCompletenessTypeAdvancedFilter($advancedFilter);
        }

        return $advancedFilter;
    }

    private function getUpdatedFilter(ProductFiltersRules $productFilterRules, SearchBuilder $queryParameters): SearchBuilder
    {
        $updatedMode = $productFilterRules->getUpdatedMode();
        if ($updatedMode === Operator::GREATER_THAN) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                $productFilterRules->getUpdatedAfter()->format(self::API_DATETIME_FORMAT)
            );
        }
        if ($updatedMode === Operator::LOWER_THAN) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                $productFilterRules->getUpdatedBefore()->format(self::API_DATETIME_FORMAT)
            );
        }
        if ($updatedMode === Operator::BETWEEN) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                [
                    $productFilterRules->getUpdatedBefore()->format(self::API_DATETIME_FORMAT),
                    $productFilterRules->getUpdatedAfter()->format(self::API_DATETIME_FORMAT),
                ]
            );
        }
        if ($updatedMode === Operator::SINCE_LAST_N_DAYS) {
            $queryParameters->addFilter(
                'updated',
                $updatedMode,
                $productFilterRules->getUpdated()
            );
        }

        return $queryParameters;
    }

    private function getExcludeFamiliesFilter(ProductFiltersRules $productFilterRules, SearchBuilder $queryParameters): SearchBuilder
    {
        if (empty($productFilterRules->getExcludeFamilies())) {
            return $queryParameters;
        }

        return $queryParameters->addFilter(
            'family',
            Operator::NOT_IN,
            $productFilterRules->getExcludeFamilies()
        );
    }

    private function getLocales(ProductFiltersRules $productFilterRules): array
    {
        if (in_array($productFilterRules->getCompletenessType(), [
            Operator::LOWER_THAN_ON_ALL_LOCALES,
            Operator::GREATER_THAN_ON_ALL_LOCALES,
            Operator::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Operator::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
        ])) {
            return $productFilterRules->getLocales();
        }

        return $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms();
    }

    private function getCompletenessFilter(
        ProductFiltersRules $productFilterRules,
        SearchBuilder $queryParameters,
        ?string $completeness,
        ?int $completenessValue = null
    ): SearchBuilder {
        $completenessType = $productFilterRules->getCompletenessType();
        if ($completeness === null || $completenessType === null) {
            return $queryParameters;
        }

        if (in_array($completenessType, [
            Operator::LOWER_THAN_ON_ALL_LOCALES,
            Operator::GREATER_THAN_ON_ALL_LOCALES,
            Operator::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Operator::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
        ])) {
            $queryParameters->addFilter(
                'completeness',
                $completeness,
                $completenessValue,
                [
                    'locales' => $productFilterRules->getLocales(),
                    'scope' => $productFilterRules->getChannel(),
                ]
            );

            return $queryParameters;
        }

        $queryParameters->addFilter(
            'completeness',
            $completeness,
            $completenessValue,
            [
                'locales' => in_array($completeness, [self::AT_LEAST_COMPLETE, self::ALL_COMPLETE]) ? $this->getLocales($productFilterRules) : [],
                'scope' => $productFilterRules->getChannel(),
            ]
        );

        return $queryParameters;
    }
}
