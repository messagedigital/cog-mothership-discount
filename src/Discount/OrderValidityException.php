<?php

namespace Message\Mothership\Discount\Discount;

/**
 * Exception used when trying to add an Discount
 * to an order which does not fulfill all criteria
 * for the discount.
 */
class OrderValidityException extends \Exception
{
}