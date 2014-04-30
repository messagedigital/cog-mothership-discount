<?php

namespace Message\Mothership\Discount\Form;

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

	public function __construct($maxCodeLength)
	{
		$this->_maxCodeLength = (int) $maxCodeLength;

		return $this;
	}

	public function setMaxCodeLength($maxCodeLength)
	{
		$this->_maxCodeLength = (int)$maxCodeLength;

		return $this;
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

		$builder->add(
			$builder->create('emails', 'textarea', [
					'label'           => 'ms.discount.discount.attributes.emails.label',
					'contextual_help' => 'ms.discount.discount.attributes.emails.help',
				]
			)
			->addModelTransformer(new DiscountEmailTransformer)
		);

		$builder->add('start', 'datetime', [
			'label'           => 'ms.discount.discount.attributes.start.label',
			'contextual_help' => 'ms.discount.discount.attributes.start.help',
		]);

		$builder->add('end', 'datetime', [
			'label'           => 'ms.discount.discount.attributes.end.label',
			'contextual_help' => 'ms.discount.discount.attributes.end.help',
		]);

		$builder->addEventListener(Form\FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
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
		$resolver->setDefaults([
		   'data_class' => 'Message\\Mothership\\Discount\\Discount\\Discount',
		]);
	}

	public function getName()
	{
		return 'discount_attributes';
	}
}