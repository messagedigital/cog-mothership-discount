<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Mothership\Discount;
use Message\Mothership\ControlPanel\Event\Event;

use Message\Cog\Bootstrap\EventsInterface;
use Message\Cog\Service\ContainerInterface;
use Message\Cog\Service\ContainerAwareInterface;

class Events implements EventsInterface, ContainerAwareInterface
{
	protected $_services;

	public function setContainer(ContainerInterface $container)
	{
		$this->_services = $container;
	}

	public function registerEvents($dispatcher)
	{
		$dispatcher->addListener(Event::BUILD_MAIN_MENU, function($event) {
			$event->addItem('ms.discount.dashboard', 'Discount', array(
				'ms.discount'
			));
		});

		$dispatcher->addSubscriber(new Discount\Discount\EventListener);
	}
}