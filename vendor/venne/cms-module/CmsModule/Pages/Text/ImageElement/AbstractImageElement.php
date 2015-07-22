<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Pages\Text\ImageElement;

use CmsModule\Content\Elements\BaseElement;
use CmsModule\Content\Elements\ExtendedElementEntity;
use CmsModule\Content\Entities\FileEntity;
use Nette\InvalidArgumentException;
use Venne\Forms\FormFactory;
use Venne\Module\Helpers;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
abstract class AbstractImageElement extends BaseElement
{

	/** @var FormFactory */
	protected $setupFormFactory;

	/** @var Helpers */
	protected $moduleHelpers;


	/**
	 * @param Helpers $moduleHelpers
	 */
	public function injectModuleHelpers(Helpers $moduleHelpers)
	{
		$this->moduleHelpers = $moduleHelpers;
	}


	/**
	 * @return array
	 */
	public function getViews()
	{
		return array(
			'setup' => 'Edit element',
		) + parent::getViews();
	}


	/**
	 * @return string
	 */
	protected function getEntityName()
	{
		return __NAMESPACE__ . '\ImageEntity';
	}


	public function renderDefault()
	{
		$this->template->image = $this->getExtendedElement()->image;
		$this->template->alt = $this->getExtendedElement()->alt;
		$this->template->width = $this->getExtendedElement()->width;
		$this->template->height = $this->getExtendedElement()->height;
		$this->template->format = $this->getExtendedElement()->format;
		$this->template->type = $this->getExtendedElement()->type;
	}


	public function renderSetup()
	{
		echo $this['form']->render();
	}


	/**
	 * @return \Venne\Forms\Form
	 */
	protected function createComponentForm()
	{
		$form = $this->setupFormFactory->invoke($this->getExtendedElement());
		$form->onSuccess[] = $this->processForm;
		return $form;
	}


	public function processForm()
	{
		$this->getPresenter()->redirect('refresh!');
	}


	protected function applyDefaults(ExtendedElementEntity $entity, $defaults)
	{
		if (isset($defaults['image']) && is_string($defaults['image'])) {
			$file = $this->moduleHelpers->expandPath($defaults['image'], 'Resources/public');

			if (!file_exists($file)) {
				throw new InvalidArgumentException("Path '{$defaults['image']}' does not exist.");
			}

			$entity->image = new FileEntity;
			$entity->image->setFile(new \SplFileInfo($file));

			unset($defaults['image']);
		}

		parent::applyDefaults($entity, $defaults);
	}

}
