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

use FormsModule\Mappers\ConfigMapper;
use Venne\Forms\Form;
use Venne\Forms\FormFactory;
use Venne\Module\ModuleManager;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class WebsiteFormFactory extends FormFactory
{

	/** @var ConfigMapper */
	private $mapper;

	/** @var ModuleManager */
	private $moduleManager;


	/**
	 * @param ConfigMapper $mapper
	 * @param ModuleManager $moduleManager
	 */
	public function __construct(ConfigMapper $mapper, ModuleManager $moduleManager)
	{
		$this->mapper = $mapper;
		$this->moduleManager = $moduleManager;
	}


	protected function getMapper()
	{
		$mapper = clone $this->mapper;
		$mapper->setRoot('cms.website');
		return $mapper;
	}


	protected function getControlExtensions()
	{
		return array(
			new \FormsModule\ControlExtensions\ControlExtension(),
		);
	}


	/**
	 * @param Form $form
	 */
	protected function configure(Form $form)
	{
		$form->addGroup('Global meta informations');
		$form->addText('name', 'Website name')->addRule($form::FILLED);
		$form->addText('title', 'Title')->setOption('description', '(%n - name, %s - separator, %t - local title)');
		$form->addText('titleSeparator', 'Title separator');
		$form->addText('keywords', 'Keywords');
		$form->addText('description', 'Description');
		$form->addText('author', 'Author');

		$form->addGroup('System');
		$form->addTextWithSelect('routePrefix', 'Route prefix');
		$form->addTextWithSelect('oneWayRoutePrefix', 'One way route prefix');
		$form->addSelect('theme', 'Module width theme', $this->getModules())
			->setPrompt('off');
		$form->addSelect('cacheMode', 'Cache strategy')->setItems(\CmsModule\Content\Entities\RouteEntity::getCacheModes(), FALSE)->setPrompt('off');
		$form['cacheMode']->addCondition($form::EQUAL, 'time')->toggle('cacheValue');

		$form->addGroup()->setOption('id', 'cacheValue');
		$form->addSelect('cacheValue', 'Minutes')->setItems(array(1, 2, 5, 10, 15, 20, 30, 40, 50, 60, 90, 120), FALSE);

		$form->setCurrentGroup();
		$form->addSaveButton('Save');
	}


	public function handleAttached($form)
	{
		$url = $form->presenter->context->httpRequest->url;
		$domain = trim($url->host . $url->scriptPath, '/') . '/';
		$params = array(
			htmlentities(''),
			htmlentities('<lang>/'),
			htmlentities("//$domain<lang>/"),
			htmlentities("//<lang>.$domain"),
			htmlentities("//<domain .*>$domain"),
			htmlentities("//<domain .*>$domain<lang>/"),
		);

		$form['routePrefix']->setItems($params, FALSE);
	}


	/**
	 * @return array
	 */
	protected function getModules()
	{
		$ret = array();
		foreach ($this->moduleManager->getModules() as $name => $module) {
			$ret[$name] = '@' . $name . 'Module';
		}
		return $ret;
	}
}
