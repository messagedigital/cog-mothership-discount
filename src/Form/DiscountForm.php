<?php

namespace Message\Mothership\Discount\Form;

use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Discount\Discount\Discount;
use Symfony\Component\Validator\Constraints;

class DiscountForm extends Form\AbstractType
{

    /**
     * maximal length of discount code
     * @var int
     */
    protected $_maxCodeLength;

    public function __construct($maxCodeLength)
    {
        $this->_maxCodeLength = (int) $maxCodeLength;
    }

    public function buildForm(Form\FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'required' => false,
            'constraints' => [
                new Constraints\NotBlank,
                new Constraints\Length(['max' => 255]),
            ],
            'label'    => 'ms.discount.discount.attributes.name.label',
            'contextual_help' => 'ms.discount.discount.attributes.name.help',
        ]);

        $builder->add('description', 'textarea', [
            'required' => false,
            'label'    => 'ms.discount.discount.attributes.description.label',
            'contextual_help' => 'ms.discount.discount.attributes.description.help',
        ]);

        $builder->add('code', 'text', [
            'constraints' => [
                new Constraints\Length(['max' => $this->_maxCodeLength]),
                new Constraints\NotBlank,
            ],
            'attr'            => ['maxlength' => $this->_maxCodeLength],
            'required'        => false,
            'label'           => 'ms.discount.discount.attributes.code.label',
            'contextual_help' => 'ms.discount.discount.attributes.code.help',
        ]);

        $builder->add('start', 'datetime', [
            'label'    => 'ms.discount.discount.attributes.start.label',
            'contextual_help' => 'ms.discount.discount.attributes.start.help',
            'data'     => new \DateTime,
            'required' => false,
        ]);

        $builder->add('end', 'datetime', [
            'label'    => 'ms.discount.discount.attributes.end.label',
            'contextual_help' => 'ms.discount.discount.attributes.end.help',
            'data' => new \DateTime,
            'required' => false,
        ]);

        $builder->addEventListener(Form\FormEvents::POST_SUBMIT, function(Form\FormEvent $event) {
            $this->validateStartEndDate($event);
            $this->filter($event->getForm()->getData());
        });
    }

    /**
     * Filtering for discount object
     */
    public function filter(Discount $discount)
    {
        $discount->name = ucfirst($discount->name);
        $discount->code = strtoupper($discount->code);
    }

    /**
     * Validate start and end date
     */
    public function validateStartEndDate(Form\FormEvent $event)
    {
        $form = $event->getForm();
        $discount = $form->getData();

        if ($discount->start !== null && $discount->end !== null && $discount->start > $discount->end) {
            $form->get('start')->addError(new Form\FormError('Start date must be before end date.'));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Message\Mothership\Discount\Discount\Discount',
            'errors_with_fields' => false,
        ));
    }

    public function getName()
    {
        return 'discount';
    }
}