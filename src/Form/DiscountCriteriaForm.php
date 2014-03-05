<?php

namespace Message\Mothership\Discount\Form;

use Message\User\User;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Discount\Discount\Discount;
use Symfony\Component\Validator\Constraints;
use Message\Cog\ValueObject\DateTimeImmutable;

class DiscountCriteriaForm extends Form\AbstractType
{
    const APPLIES_TO_PRODUCTS = 0;
    const APPLIES_TO_ORDER    = 1;

    /**
     * User set on the authorship object
     */
    protected $_user;

    /**
     * All products available
     * @var array
     */
    protected $_products;

    /**
     * Available currencies
     * @var array
     */
    protected $_currencies;

    public function __construct(array $products, array $currencies, User $user)
    {
        $this->_currencies = $currencies;
        $this->_products = $products;
        $this->_user = $user;

        return $this;
    }

    public function setUser(User $user)
    {
        $this->_user = $user;
    }

    public function setProducts(array $products)
    {
        $this->_products = $products;
    }

    public function setCurrencies(array $currencies)
    {
        $this->_currencies = $currencies;
    }

    public function buildForm(Form\FormBuilderInterface $builder, array $options)
    {
        $builder->add('products', 'entity', [
            'label'    => 'ms.discount.discount.criteria.products.label',
            'property' => 'displayName',
            'choices'  => $this->_products,
            'mapped'   => true,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ]);

        $builder->addEventListener(Form\FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(Form\FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));

    }

    public function onPreSetData(Form\FormEvent $event)
    {
        $form = $event->getForm();
        $discount = $event->getData();

        foreach ($this->_currencies as $currencyID) {
            $form->add($currencyID, 'money', [
                'currency'    => $currencyID,
                'data'        => $discount->getThresholdForCurrencyID($currencyID),
                'required'    => false,
                'label'       => false,
                'mapped'      => false,
                'constraints' => new Constraints\GreaterThan(['value' => 0]),
            ]);
        }

        $form->add('appliesTo', 'choice', [
            'label'       => 'ms.discount.discount.criteria.applies-to.label',
            'choices'     => [
                self::APPLIES_TO_PRODUCTS => 'ms.discount.discount.criteria.applies-to.choices.products.label',
                self::APPLIES_TO_ORDER    => 'ms.discount.discount.criteria.applies-to.choices.order.label',
            ],
            'mapped'      => false,
            'data'        => (0 === count($discount->products) ? self::APPLIES_TO_ORDER : self::APPLIES_TO_PRODUCTS),
            'multiple'    => false,
            'expanded'    => false,
            'constraints' => new Constraints\NotBlank,
        ]);
    }

    public function onPostSubmit(Form\FormEvent $event)
    {
        $this->validate($event->getForm());
        $this->processCurrencies($event);
    }

    public function validate(Form\FormInterface $form)
    {
        $discount = $form->getData();

        if(self::APPLIES_TO_ORDER === $form->get('appliesTo')->getData() && 0 !== count($discount->products)) {
            $form->get('products')->addError(new Form\FormError('No products can be chosen if the
                discount applies to a whole order. Please either deselect the products or change
                `Applies to` to `Specific Products Only`.'));
        } elseif(self::APPLIES_TO_PRODUCTS === $form->get('appliesTo')->getData() && 0 === count($discount->products)) {
            $form->get('products')->addError(new Form\FormError('Please choose at least one product the discount
                can be applied to or change `Applies to` to `Whole Order`.'));
        }
    }

    /**
     * Processes currencies and adds them to discount
     */
    public function processCurrencies(Form\FormEvent $event)
    {
        $form = $event->getForm();
        $discount = $form->getData();

        foreach($this->_currencies as $currencyID) {
            $amount = $form->get($currencyID)->getData();
            $discount->addThreshold($currencyID, $amount);
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
        return 'discount_criteria';
    }
}