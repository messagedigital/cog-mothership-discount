<?php

namespace Message\Mothership\Discount\Form;

use Message\User\User;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Discount\Discount\Discount;
use Message\Mothership\Discount\Discount\DiscountAmount;
use Symfony\Component\Validator\Constraints;
use Message\Cog\ValueObject\DateTimeImmutable;

class DiscountBenefitForm extends Form\AbstractType
{
    /**
     * Available currencies
     * @var array
     */
    protected $_currencies;

    /**
     * User set on the authorship object
     */
    protected $_user;

    public function __construct(array $currencies, User $user)
    {
        $this->_currencies = $currencies;
        $this->_user = $user;

        return $this;
    }

    public function setUser(User $user)
    {
        $this->_user = $user;
    }

    public function setCurrencies(array $currencies)
    {
        $this->_currencies = $currencies;
    }

    public function buildForm(Form\FormBuilderInterface $builder, array $options)
    {

        $builder->add('percentage', 'percent', [
            'label'    => 'ms.discount.discount.benefit.percentage.label',
            'type'     => 'integer',
            'constraints' => [
                new Constraints\GreaterThan(['value' => 0]),
                new Constraints\LessThanOrEqual(['value' => 100]),
            ]
        ]);

        $builder->add('discountAmounts', 'currency_set', [
            'label' => 'ms.discount.discount.benefit.discount-amounts.label',
            'options' => [
                'label' => false,
            ],
        ]);

        $builder->add('freeShipping', 'checkbox', [
            'label'           => 'ms.discount.discount.benefit.free-shipping.label',
            'contextual_help' => 'ms.discount.discount.benefit.free-shipping.help',
        ]);

        $builder->addEventListener(Form\FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPostSubmit(Form\FormEvent $event)
    {
        $this->validate($event->getForm());
    }

    public function validate(Form\FormInterface $form)
    {
        if(null !== $form->get('percentage')->getData() && array() !== $form->get('discountAmounts')->getData()) {
            $form->addError(new Form\FormError('Please only fill in either a percentage OR a fixed discount.'));
        } elseif(null === $form->get('percentage')->getData() && array() === $form->get('discountAmounts')->getData() && false === $form->get('freeShipping')->getData()) {
            $form->addError(new Form\FormError('Neither a percentage discount, nor a fixed discount amount, nor free shipping have been added to this discount.'));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Message\Mothership\Discount\Discount\Discount',
        ));
    }

    public function getName()
    {
        return 'discount_benefit';
    }
}