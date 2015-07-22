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

use CmsModule\Content\Entities\LanguageEntity;
use CmsModule\Content\Entities\PageEntity;
use CmsModule\Content\Listeners\PageListener;
use CmsModule\Content\Repositories\LanguageRepository;
use CmsModule\Content\Repositories\PageRepository;
use CmsModule\Content\Repositories\RouteRepository;
use CmsModule\Content\WebsiteManager;
use Venne\Application\UI\Presenter;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class SitemapPresenter extends Presenter
{

	/** @persistent */
	public $page;

	/** @persistent */
	public $lang;

	/** @var LanguageRepository */
	private $languageRepository;

	/** @var PageRepository */
	private $pageRepository;

	/** @var RouteRepository */
	private $routeRepository;

	/** @var PageListener */
	private $pageListener;

	/** @var int */
	private $itemsLimit = 100;

	/** @var LanguageEntity */
	private $_language;

	/** @var WebsiteManager */
	private $websiteManager;


	/**
	 * @param WebsiteManager $websiteManager
	 * @param PageListener $pageListener
	 * @param LanguageRepository $languageRepository
	 * @param PageRepository $pageRepository
	 * @param RouteRepository $routeRepository
	 */
	public function __construct(WebsiteManager $websiteManager, PageListener $pageListener, LanguageRepository $languageRepository, PageRepository $pageRepository, RouteRepository $routeRepository)
	{
		parent::__construct();

		$this->websiteManager = $websiteManager;
		$this->languageRepository = $languageRepository;
		$this->pageRepository = $pageRepository;
		$this->routeRepository = $routeRepository;
		$this->pageListener = $pageListener;


		$this->absoluteUrls = true;

		\Nette\Diagnostics\Debugger::$bar = false;
	}


	protected function startup()
	{
		parent::startup();

		if ($this->lang !== $this->websiteManager->defaultLanguage) {
			$this->pageListener->setLocale($this->getLanguage());
		}
	}


	protected function beforeRender()
	{
		parent::beforeRender();

		$this->template->routePrefix = $this->websiteManager->routePrefix;
		$this->template->languageRepository = $this->languageRepository;
		$this->template->pageRepository = $this->pageRepository;
		$this->template->itemsLimit = $this->itemsLimit;
		$this->template->defaultLanguage = $this->websiteManager->defaultLanguage;
	}


	public function countRoutesByPage(PageEntity $page)
	{
		return $this->routeRepository->createQueryBuilder('a')
			->select('COUNT(a.id)')
			->where('a.page = :page')->setParameter('page', $page->id)
			->getQuery()->getSingleScalarResult();
	}


	public function countPagesByLanguage(LanguageEntity $language = NULL)
	{
		$qb = $this->pageRepository->createQueryBuilder('a')
			->select('COUNT(a.id)');

		if ($language) {
			$qb->where('a.language = :language')->setParameter('language', $language->id);
		} else {
			$qb->where('a.language IS NULL');
		}

		return $qb->getQuery()->getSingleScalarResult();
	}


	public function getLanguage()
	{
		if (!$this->_language) {
			$this->_language = $this->languageRepository->findOneBy(array('alias' => $this->lang));
		}
		return $this->_language;
	}


	public function getRoutes()
	{
		if ($this->page) {
			return $this->routeRepository->findBy(array('page' => $this->page));
		}

		$lang = $this->getLanguage();
		$ids = array();

		$pages = $this->pageRepository->createQueryBuilder('a')
			->where('(a.language IS NULL OR a.language = :lang)')->setParameter('lang', $lang->id)
			->getQuery()->getResult();

		foreach ($pages as $page) {
			if ($this->countRoutesByPage($page) <= $this->itemsLimit) {
				$ids[] = $page->id;
			}
		}

		return $this->routeRepository->createQueryBuilder('a')
			->andWhere('a.page IN (:ids)')->setParameter('ids', $ids)
			->andWhere('a.published = :true')->setParameter('true', TRUE)
			->getQuery()->getResult();
	}
}

