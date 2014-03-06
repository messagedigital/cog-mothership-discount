<?php

namespace Message\Mothership\Discount\Form;

use Message\User\User;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Discount\Discount\Discount;
use Symfony\Component\Validator\Constraints;
use Message\Cog\ValueObject\DateTimeImmutable;

class DiscountAttributesForm extends Form\AbstractType
{

    /**
     * Maximum length of discount code
     * @var int
     */
    protected $_maxCodeLength;

    /**
     * User set on the authorship object
     */
    protected $_user;

    public function __construct($maxCodeLength, User $user)
    {
        $this->_maxCodeLength = (int) $maxCodeLength;
        $this->_user = $user;

        return $this;
    }

    public function setMaxCodeLength($maxCodeLength)
    {
        $this->_maxCodeLength = (int)$maxCodeLength;

        return $this;
    }

    public function setUser(User $user)
    {
        $this->_user = $user;
    }

    public function buildForm(Form\FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'constraints'     => [
                new Constraints\NotBlank,
                new Constraints\Length(['max' => 255]),
            ],
            'label'           => 'ms.discount.discount.attributes.name.label',
            'contextual_help' => 'ms.discount.discount.attributes.name.help',
        ]);

        $builder->add('description', 'textarea', [
            'label'           => 'ms.discount.discount.attributes.description.label',
            'contextual_help' => 'ms.discount.discount.attributes.description.help',
        ]);

        $builder->add('start', 'datetime', [
            'label'           => 'ms.discount.discount.attributes.start.label',
            'contextual_help' => 'ms.discount.discount.attributes.start.help',
            'data'            => new \DateTime,
        ]);

        $builder->add('end', 'datetime', [
            'label'           => 'ms.discount.discount.attributes.end.label',
            'contextual_help' => 'ms.discount.discount.attributes.end.help',
            'data'            => new \DateTime,
        ]);

        $builder->addEventListener(Form\FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);

        $builder->addEventListener(Form\FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    public function onPreSetData(Form\FormEvent $event) {
        $form = $event->getForm();
        $discount = $event->getData();

        // dynamically add code field if discount not created yet
        if(!$discount || null === $discount->id) {
            $form->add('code', 'text', [
                'constraints' => [
                    new Constraints\Length(['max' => $this->_maxCodeLength]),
                    new Constraints\NotBlank,
                ],
                'attr'            => ['maxlength' => $this->_maxCodeLength],
                'label'           => 'ms.discount.discount.attributes.code.label',
                'contextual_help' => 'ms.discount.discount.attributes.code.help',
            ]);
        }
    }

    /**
     * Method called on Form\FormEvents::POST_SUBMIT
     * @param  FormFormEvent $event
     */
    public function onPostSubmit(Form\FormEvent $event) {
        $this->validateStartEndDate($event);
        $this->filter($event->getForm()->getData());
    }

    /**
     * Filtering for discount object
     */
    public function filter(Discount $discount)
    {
        $discount->name = ucfirst($discount->name);
        $discount->code = strtoupper($discount->code);
        if(null === $discount->id) {
            $discount->authorship->create(new DateTimeImmutable, $this->_user->id);
        }
    }

    /**
     * Validate start and end date
     */
    public function validateStartEndDate(Form\FormEvent $event)
    {
        $form = $event->getForm();
        $discount = $form->getData();

        if (null !== $discount->start && null !== $discount->end && $discount->start > $discount->end) {
            $form->get('start')->addError(new Form\FormError('Start date must be before end date.'));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Message\Mothership\Discount\Discount\Discount',
            'required' => false,
        ));
    }

    public function getName()
    {
        return 'discount_attributes';
    }
}