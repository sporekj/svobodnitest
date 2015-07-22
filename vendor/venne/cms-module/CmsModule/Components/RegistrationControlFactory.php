<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Components;

use Nette\Callback;
use Venne\BaseFactory;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class RegistrationControlFactory extends BaseFactory
{

	/** @var Callback */
	private $factory;


	/**
	 * @param Callback $factory
	 */
	public function __construct(Callback $factory)
	{
		$this->factory = $factory;
	}


	/**
	 * @return RegistrationControl
	 */
	public function invoke($userType, $mode, $loginProviderMode, $roles, $emailSender, $emailFrom, $emailSubject, $emailText)
	{
		return Callback::create($this->factory)->invoke($userType, $mode, $loginProviderMode, $roles, $emailSender, $emailFrom, $emailSubject, $emailText);
	}

}
