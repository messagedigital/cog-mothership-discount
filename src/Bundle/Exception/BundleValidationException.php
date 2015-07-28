<?php

namespace Message\Mothership\Discount\Bundle\Exception;

/**
 * Class BundleValidationException
 * @package Message\Mothership\Discount\Bundle\Exception
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Exception to be thrown when a bundle is not valid on the current order. The message will be displayed to the customer
 */
class BundleValidationException extends \LogicException
{

}