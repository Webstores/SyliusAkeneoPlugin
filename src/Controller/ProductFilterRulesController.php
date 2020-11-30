<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleSimpleType;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

final class ProductFilterRulesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var EntityRepository */
    private $apiConfigurationRepository;

    /** @var FactoryInterface */
    private $productFilterRulesFactory;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        EntityRepository $apiConfigurationRepository,
        FactoryInterface $productFilterRulesFactory,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->productFilterRulesFactory = $productFilterRulesFactory;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    public function updateAction(int $id, Request $request): Response
    {
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);
        if (!$apiConfiguration instanceof ApiConfiguration) {
            $this->flashBag->add('error', $this->translator->trans('sylius.ui.admin.akeneo.not_configured_yet'));

            return $this->redirectToRoute('sylius_akeneo_connector_api_configuration');
        }

        $productFiltersRules = $this->productFiltersRulesRepository->find($id);
        if (!$productFiltersRules instanceof ProductFiltersRules) {
            $this->flashBag->add('error', $this->translator->trans('sylius.ui.admin.akeneo.product_filter_rules.update.not_found'));

            return $this->redirectToRoute('akeneo_admin_product_filters_rules_index');
        }

        $simpleForm = $this->handleForm($request, $productFiltersRules, ProductFilterRuleSimpleType::class);

        $advancedForm = $this->handleForm($request, $productFiltersRules, ProductFilterRuleAdvancedType::class);

        if ($simpleForm->isSubmitted() && $simpleForm->isValid()) {
            $this->update($simpleForm);
        }

        if ($advancedForm->isSubmitted() && $advancedForm->isValid()) {
            $this->update($advancedForm);
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/update_filter_configuration.html.twig', [
            'simple_form' => $simpleForm->createView(),
            'advanced_form' => $advancedForm->createView(),
        ]);
    }

    public function createAction(Request $request): Response
    {
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);
        if (!$apiConfiguration instanceof ApiConfiguration) {
            $this->flashBag->add('error', $this->translator->trans('sylius.ui.admin.akeneo.not_configured_yet'));

            return $this->redirectToRoute('sylius_akeneo_connector_api_configuration');
        }

        /** @var ProductFiltersRules $productFiltersRules */
        $productFiltersRules = $this->productFilterRulesFactory->createNew();

        $simpleForm = $this->handleForm($request, $productFiltersRules, ProductFilterRuleSimpleType::class);

        $advancedForm = $this->handleForm($request, $productFiltersRules, ProductFilterRuleAdvancedType::class);

        if ($simpleForm->isSubmitted() && $simpleForm->isValid()) {
            $this->update($simpleForm);
        }

        if ($advancedForm->isSubmitted() && $advancedForm->isValid()) {
            $this->update($advancedForm);
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/create_filter_configuration.html.twig', [
            'simple_form' => $simpleForm->createView(),
            'advanced_form' => $advancedForm->createView(),
        ]);
    }

    private function handleForm(Request $request, ProductFiltersRules $productFiltersRules, string $type): FormInterface
    {
        $form = $this->createForm($type, $productFiltersRules);
        $form->handleRequest($request);

        return $form;
    }

    private function update(FormInterface $form): Response
    {
        $this->entityManager->persist($form->getData());
        $this->entityManager->flush();

        $this->flashBag->add('success', $this->translator->trans('akeneo.ui.admin.changes_successfully_saved'));

        return $this->redirectToRoute('akeneo_admin_product_filters_rules_index');
    }
}
