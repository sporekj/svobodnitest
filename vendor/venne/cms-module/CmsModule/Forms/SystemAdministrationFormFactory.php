<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Forms;

use CmsModule\Content\WebsiteManager;
use FormsModule\Mappers\ConfigMapper;
use Nette\Http\Request;
use Venne\Forms\Form;
use Venne\Forms\FormFactory;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class SystemAdministrationFormFactory extends FormFactory
{

	/** @var ConfigMapper */
	protected $mapper;

	/** @var Request */
	protected $httpRequest;

	/** @var WebsiteManager */
	protected $websiteManager;


	/**
	 * @param WebsiteManager $websiteManager
	 * @param ConfigMapper $mapper
	 * @param Request $httpRequest
	 */
	public function __construct(WebsiteManager $websiteManager, ConfigMapper $mapper, Request $httpRequest)
	{
		$this->mapper = $mapper;
		$this->httpRequest = $httpRequest;
		$this->websiteManager = $websiteManager;
	}


	protected function getMapper()
	{
		$mapper = clone $this->mapper;
		$mapper->setRoot('cms.administration');
		return $mapper;
	}


	/**
	 * @param Form $form
	 */
	protected function configure(Form $form)
	{
		$form->addGroup('Administration settings');
		$form->addText('routePrefix', 'Route prefix');
		$form->addText('defaultPresenter', 'Default presenter');

		$form->setCurrentGroup();
		$form->addSaveButton('Save');
	}


	public function handleSuccess($form)
	{
		$form->getPresenter()->absoluteUrls = true;
		$url = $this->httpRequest->getUrl();

		$path = "{$url->scheme}://{$url->host}{$url->scriptPath}";

		$oldPath = $path . $this->websiteManager->routePrefix;
		$newPath = $path . $form['routePrefix']->getValue();

		if ($form['routePrefix']->getValue() == '') {
			$oldPath .= '/';
		}

		if ($this->websiteManager->routePrefix == '') {
			$newPath .= '/';
		}

		$form->getPresenter()->flashMessage('Administration settings has been updated', 'success');
		$form->getPresenter()->redirectUrl(str_replace($oldPath, $newPath, $form->getPresenter()->link('this')));
	}
}
