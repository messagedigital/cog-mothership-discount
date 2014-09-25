<?php

namespace Message\Mothership\Discount\Form;

use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

use Message\Cog\Db\Query;
use Message\Mothership\Commerce\Product\Loader;
use Message\Mothership\Discount\Form\DataTransformer\DiscountEmailTransformer;
use Message\Mothership\Discount\Form\DataTransformer\DiscountProductTransformer;

class DiscountCriteriaForm extends Form\AbstractType
{
	const APPLIES_TO_PRODUCTS = 0;
	const APPLIES_TO_ORDER    = 1;

	/**
	 * @var \Message\Cog\Db\Query
	 */
	protected $_query;

	/**
	 * @var \Message\Mothership\Commerce\Product\Loader
	 */
	protected $_productLoader;

	/**
	 * All products available
	 * @var array
	 */
	protected $_products;

	public function __construct(Query $query, Loader $productLoader)
	{
		$this->_query           = $query;
		$this->_productLoader   = $productLoader;
		$this->_products        = $this->_getProducts();

		return $this;
	}

	public function setProducts(array $products)
	{
		$this->_products = $products;
	}

	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$builder->add('thresholds', 'currency_set', [
			'label'   => 'ms.discount.discount.criteria.thresholds.label',
			'options' => [
				'label' => false,
			],
		]);

		$builder->add(
			$builder->create('products', 'choice', [
				'label'    => 'ms.discount.discount.criteria.products.label',
				'choices'  => $this->_products,
				'multiple' => true,
				'expanded' => true,
			])
				->addModelTransformer(new DiscountProductTransformer($this->_productLoader))
		);

		$builder->add(
			$builder->create('emails', 'textarea', [
					'label'           => 'ms.discount.discount.criteria.emails.label',
					'contextual_help' => 'ms.discount.discount.criteria.emails.help',
				]
			)
				->addModelTransformer(new DiscountEmailTransformer)
		);

		$builder->addEventListener(Form\FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
		$builder->addEventListener(Form\FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));

	}

	public function onPreSetData(Form\FormEvent $event)
	{
		$form = $event->getForm();
		$discount = $event->getData();

		$form->add('appliesTo', 'choice', [
			'label'       => 'ms.discount.discount.criteria.applies-to.label',
			'choices'     => [
				self::APPLIES_TO_PRODUCTS => 'ms.discount.discount.criteria.applies-to.choices.products.label',
				self::APPLIES_TO_ORDER    => 'ms.discount.discount.criteria.applies-to.choices.order.label',
			],
			'mapped'      => false,
			'data'        => (0 === $discount->getProducts()->count() ? self::APPLIES_TO_ORDER : self::APPLIES_TO_PRODUCTS),
			'multiple'    => false,
			'expanded'    => false,
			'constraints' => new Constraints\NotBlank,
		]);
	}

	public function onPostSubmit(Form\FormEvent $event)
	{
		$this->validate($event->getForm());
	}

	public function validate(Form\FormInterface $form)
	{
		$discount = $form->getData();

		if(self::APPLIES_TO_ORDER === $form->get('appliesTo')->getData() && 0 !== $discount->getProducts()->count()) {
			$form->get('products')->addError(new Form\FormError('No products can be chosen if the
				discount applies to a whole order. Please either deselect the products or change
				`Applies to` to `Specific Products Only`.'));
		} elseif(self::APPLIES_TO_PRODUCTS === $form->get('appliesTo')->getData() && 0 === $discount->getProducts()->count()) {
			$form->get('products')->addError(new Form\FormError('Please choose at least one product the discount
				can be applied to or change `Applies to` to `Whole Order`.'));
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
		return 'discount_criteria';
	}

	protected function _getProducts()
	{
		$result = $this->_query->run("
			SELECT
				product_id,
				name
			FROM
				product
			WHERE
				deleted_at IS NULL
			ORDER BY
				name ASC
		");

		$products = [];

		foreach ($result as $row) {
			$products[$row->product_id] = $row->name;
		}

		return $products;
	}
}