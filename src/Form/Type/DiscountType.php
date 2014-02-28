<?php

namespace Message\Mothership\Discount\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints;

class DiscountType extends AbstractType
{

    protected $_maxCodeLength;

    public function __construct($maxCodeLength)
    {
        $this->_maxCodeLength = (int) $maxCodeLength;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'required' => false,
            'constraints' => [
                new Constraints\NotBlank,
                new Constraints\Length(['max' => 255]),
            ]
        ])
            // ->titlecase()
            ;

        $builder->add('description', 'textarea', [
            'required' => false,
        ]);

        $builder->add('code', 'text', [
            'constraints' => [
                new Constraints\Length(['max' => $this->_maxCodeLength]),
                new Constraints\NotBlank,
            ],
            'attr' => ['maxlength' => $this->_maxCodeLength],
            'required' => false,
            // UPPERCASE filter!
            ]
        );

        $builder->add('start', 'datetime', [
                'label'    => 'Start Date',
                'data'     => new \DateTime,
                'required' => false,
            ]
        );

        $builder->add('end', 'datetime', [
                'label'    => 'End Date',
                'data' => new \DateTime,
                'required' => false,
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function($event) {
            $form = $event->getForm();
            $discount = $form->getData();

            // $discount->start = ($data['start'] !== null ? $data['start'] : null);
            // $discount->end   = ($data['end']   !== null ? $data['end']   : null);

            if ($discount->start !== null && $discount->end !== null && $discount->start > $discount->end) {
                $form->get('start')->addError(new FormError('Start date must be before end date.'));
            }

        }, 100);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Message\Mothership\Discount\Discount\Discount',
        ));
    }

    public function getName()
    {
        return 'discount';
    }
}