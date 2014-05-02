<?php

namespace Message\Mothership\Discount\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DiscountEmailTransformer implements DataTransformerInterface
{
	public function transform($emails)
	{
		$emails = (array) $emails;
		$emails = implode(PHP_EOL, $emails);

		return $emails;
	}

	public function reverseTransform($emails)
	{
		$emails = (string) $emails;

		$emails = explode(PHP_EOL, $emails);

		return $this->_parseEmails($emails);
	}

	protected function _parseEmails(array $emails)
	{
		foreach ($emails as $key => $email) {
			$email        = strtolower(trim($email));
			$emails[$key] = $email;
		}

		return array_unique($emails);
	}
}