<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Presenters;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 *
 * @property-read string $defaultLanguageAlias
 */
abstract class FrontPresenter extends BasePresenter
{

	/**
	 * Redirect to other language.
	 *
	 * @param string $alias
	 */
	public function handleChangeLanguage($alias)
	{
		$this->redirect('this', array('lang' => $alias));
	}


	protected function checkLanguage()
	{
		if (count($this->websiteManager->languages) > 1) {
			if (!$this->lang && !$this->getParameter('lang')) {
				$this->lang = $this->getDefaultLanguageAlias();
			}
		} else {
			$this->lang = $this->websiteManager->defaultLanguage;
		}
	}


	/**
	 * @return string
	 */
	protected function getDefaultLanguageAlias()
	{
		$httpRequest = $this->context->httpRequest;

		$lang = $httpRequest->detectLanguage($this->websiteManager->languages);
		if (!$lang) {
			$lang = $this->websiteManager->defaultLanguage;
		}
		return $lang;
	}
}
