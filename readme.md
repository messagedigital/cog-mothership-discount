# Mothership Discount

## Discount\Discount
The Discount-class is the entity in the heart of the discount-cogule.
Discounts consist of (at least) an id, a code and a name.

### Benefit
A discount has information about it's benefit.
It either has a fixed amount or a percentage discount, moreover free shipping can be applied:
	
	$discount->percentage = 40;
	$discount->freeshipping = true;
	
Fixed discounts can be added like this:

	$amount = new DiscountAmount();
	$amount->currencyID = 'GBP';
	$amount->locale = 'en_GB';
	$amount->amount = 20.5;
	
	$discount->addDiscountAmount($amount);
	
And you can get a discount amount for a certain currency ID like this:

	$discount->getDiscountAmountForCurrencyID('GBP'); // returns $amount
	$discount->getDiscountAmountForCurrencyID('EUR'); // returns null


### Activity
To define whether a discount is active, it has a start and end date.
If the start AND end-date are null, it means the discount is always active, if only one of them is set, the discount is always active after the start/before the end-date.
Both start and end-date are DateTime-Objects.

You can find out whether a discount is currently active or not by using:
	
	$discount->isActive(); // returns boolean

### Criteria
A discount can define thresholds for certain locales and currencies. If the threshold is not reached, the order can not use the discount.

Thresholds can be added using `addThreshold($threshold)`. To get a threshold, you best use `getThresholdForCurrencyID($currencyID)`:

	$threshold = new Threshold();
	$threshold->currencyID = 'GBP';
	$threshold->locale = 'en_GB';
	$threshold->threshold = 20.5;
	
	$discount->addThreshold($threshold);
	$discount->getThresholdForCurrencyID('GBP'); // returns $threshold
	$discount->getThresholdForCurrencyID('EUR'); // returns null

Moreover discounts can either apply to a whole order or to specific products only.
When the `products`-Array is empty, this means that the discount applies to the whole order.
To test this, you can use:

	$discount->appliesToOrder() // true if $products is empty
	
## Discount Decorators
Discounts can be created, edited, deleted and loaded:
	
	$discount = new Discount();
	$discount->name = "Test Discount";
	$discount->code = "UNIQUECODE";
	
	$discount = $this->get('discount.create')->create($discount);
	$discount->name = 'Test Change';
	
	$discount = $this->get('discount.edit')->save($discount);
	
	$discount = $this->get('discount.delete')->delete($discount);
	$discount = $this->get('discount.delete')->restore($discount);
	
	
The loader can be used like this:	
	
	$discount = $this->get('discount.loader')->getByCode("UNIQUECODE");
	
You can load discounts using:

* Will only return one discount or false	
	* `getByID($id)` (will return one discount or false)
	* `getByCode($id)` (will return one discount or false)
	
* Will return array of discounts:
	* `getAll()`
	* `getActive()`
	* `getInactive()` (both upcoming and expired) 
	* `getByProduct($product)`
	* `getByDateRange($from, $to)` (returns discounts active between $from and $to)


	
## Discount\OrderDiscountFactory
To create a `Commerce\Order\Entity\Discount\Discount` (further referred to as `OrderDiscount`) from a `Discount\Discount` and a `Commerce\Order\Order`, you can use the `Discount\OrderDiscountFactory`-class.
The use is pretty straight forward and therefore doesn't really require a lot of explanation:

	$orderDiscountFactory = $this->get('discount.order-discount-factory')
		->setOrder($order)
		->setDiscount($discount);
		
	$orderDiscount = $orderDiscountFactory->createOrderDiscount();
	
## Discount\Validator
The Validator is a component which tries to apply a discount to a certain order.
If the discount is not applicable to the order, `OrderValidityException`s will be thrown.
Otherwise the validator returns the order-discount-object(using the OrderDiscountFactory) for the given discount-code and order.
From inside a controller you can use the validator using the `discount.validator`-Service. This could look like this:

	$discountValidator = $this->get('discount.validator')
		->setOrder($order);

	try {
		$orderDiscount = $discountValidator->validate($code);
	} catch (Discount\OrderValidityException $e) {
		$this->addFlash('error', $e->getMessage());
	}

	if($orderDiscount) { // validator returns orderDiscount-object on success
		$order->discounts->append($orderDiscount);
		$this->addFlash('success', 'You successfully added a discount');
	}
	
## EventListeners and integration with Commerce\Order
To integrate the Discount-cogule with the Basket in Commerce, there is an `Discount\EventListener`, which listens to the `CREATE_START` and `ASSEMBLER_UPDATE` Order-Events to validate and update discounts.
This is important because we want to be able to edit both discounts and the basket at any time.
The actual work on setting discount-totals and item-discounts on the order is happening in `Commerce` and only uses OrderDiscounts.
This EventListener validates whether an order can still have a certain discount after a change(e.g. removing an item) and removes the discount if not.
Furthermore it updates the OrderDiscount by loading the latest verion of the discount(using the discount code).

## ToDo

* Currency Collection necessary for iterating over all currencies in create-view!
* Validation for Start/End-Date and Percentage/Fixed Amount instead of checking it in the controller
* Add a method to add discount-amount if `freeShipping` is enabled on the discount.
* Translations!
