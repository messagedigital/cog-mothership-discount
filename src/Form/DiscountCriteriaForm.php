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

        $builder->addEventListener(Form\FormEvents::POST_SET_DATA, [$this, 'onPreSetData']);
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

        $form->add('appliesToOrder', 'choice', [
            'label'       => 'ms.discount.discount.criteria.applies-to.label',
            'choices'     => [
                self::PRODUCT => 'Specific Products Only',
                self::ORDER   => 'Whole Order',
            ],
            'mapped'      => false,
            'data'        => (0 === count($discount->products)),
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
        if() {
            $form->addError(new Form\FormError('Please only fill in either a percentage OR a fixed discount.'));
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
            $discountAmount = new DiscountAmount;
            $discountAmount->amount = $amount;
            $discountAmount->currencyID = $currencyID;

            $discount->addDiscountAmount($discountAmount);
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