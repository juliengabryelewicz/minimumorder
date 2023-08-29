<?php

declare(strict_types=1);

namespace Jgabryelewicz\Minimumorder\Form\Modifier;

use PrestaShopBundle\Form\FormBuilderModifier;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

final class CustomerFormModifier
{
    /**
     * @var FormBuilderModifier
     */
    private $formBuilderModifier;

    /**
     * @param FormBuilderModifier $formBuilderModifier
     */
    public function __construct(
        FormBuilderModifier $formBuilderModifier
    ) {
        $this->formBuilderModifier = $formBuilderModifier;
    }

    /**
     * @param int|null $customerId
     * @param FormBuilderInterface $customerFormBuilder
     * @param float $minimumPrice
     */
    public function modify(
        int $customerId,
        FormBuilderInterface $customerFormBuilder,
        float $minimumPrice
    ): void {     
        $this->formBuilderModifier->addAfter(
            $customerFormBuilder,
            'default_group_id',
            'minimum_price_order',
            MoneyType::class, [
                'label' => 'Montant minimum du panier',
                'required' => false,
                'data' => $minimumPrice
            ]
        );
    }
}