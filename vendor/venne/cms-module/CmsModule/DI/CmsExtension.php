<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\DI;

use Nette\Application\Routers\Route;
use Venne\Config\CompilerExtension;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class CmsExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = array(
		'administration' => array(
			'login' => array(
				'name' => '',
				'password' => ''
			),
			'routePrefix' => 'admin',
			'defaultPresenter' => 'Dashboard',
			'authentication' => array(
				'autologin' => NULL,
				'autoregistration' => NULL,
				'forgotPassword' => array(
					'enabled' => FALSE,
					'emailSubject' => 'Password reset',
					'emailText' => 'Reset your passord on address \%link\%.',
					'emailSender' => 'Venne:CMS',
					'emailFrom' => 'info@venne.cz',
				),
			),
			'registrations' => array(),
			'theme' => 'cms',
		),
		'website' => array(
			'name' => 'Blog',
			'title' => '%n %s %t',
			'titleSeparator' => '|',
			'keywords' => '',
			'description' => '',
			'author' => '',
			'robots' => 'index, follow',
			'routePrefix' => '',
			'oneWayRoutePrefix' => '',
			'languages' => array(),
			'defaultLanguage' => 'cs',
			'defaultPresenter' => 'Homepage',
			'errorPresenter' => 'Cms:Error',
			'layout' => '@cms/bootstrap',
			'cacheMode' => '',
			'cacheValue' => '10',
			'theme' => '',
		),
	);


	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 * @return void
	 */
	public function loadConfiguration()
	{
		$this->compiler->parseServices(
			$this->getContainerBuilder(),
			$this->loadFromFile(dirname(dirname(__DIR__)) . '/Resources/config/config.neon')
		);

		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		foreach ($config['administration']['registrations'] as $key => $values) {
			if (isset($values['name']) && $values['name']) {
				$config['administration']['registrations'][$values['name']] = $values;
				unset($config['administration']['registrations'][$key]);
			}
		}

		$container->addDependency($container->parameters['tempDir'] . '/installed');

		// http
		$httpResponse = $container->getDefinition('httpResponse');
		foreach ($httpResponse->setup as $setup) {
			if ($setup->entity == 'setHeader' && $setup->arguments[0] == 'X-Powered-By') {
				$httpResponse->addSetup('setHeader', array('X-Powered-By', $setup->arguments[1] . ' && Venne:CMS'));
			}
		}

		// security
		$container->getDefinition('nette.userStorage')
			->setClass('CmsModule\Security\UserStorage', array('@session', '@cms.loginRepository', '@cms.userRepository', '@doctrine.checkConnectionFactory'));

		// Application
		$application = $container->getDefinition('application');
		$application->addSetup('$service->errorPresenter = ?', $config['website']['errorPresenter']);

		$container->addDefinition('authorizatorFactory')
			->setFactory('CmsModule\Security\AuthorizatorFactory', array('@nette.presenterFactory', '@cms.roleRepository', '@session', '@doctrine.checkConnectionFactory'))
			->addSetup('setReader');

		$container->getDefinition('venne.moduleManager')
			->addSetup('$service->onInstall[] = ?->clearPermissionSession', array('@authorizatorFactory'))
			->addSetup('$service->onUninstall[] = ?->clearPermissionSession', array('@authorizatorFactory'));

		$container->addDefinition('authorizator')
			->setClass('Nette\Security\Permission')
			->setFactory('@authorizatorFactory::getPermissionsByUser', array('@user', TRUE));

		$container->addDefinition('authenticator')
			->setClass('CmsModule\Security\Authenticator', array($config['administration']['login']['name'], $config['administration']['login']['password'], '@doctrine.checkConnectionFactory', '@cms.userRepository'));

		// detect prefix
		$prefix = $config['website']['routePrefix'];
		$adminPrefix = $config['administration']['routePrefix'];
		$languages = $config['website']['languages'];
		$prefix = str_replace('<lang>/', '<lang ' . implode('|', $languages) . '>/', $prefix);

		// parameters
		$parameters = array();
		$parameters['lang'] = count($languages) > 1 || $config['website']['routePrefix'] ? NULL : $config['website']['defaultLanguage'];

		// Sitemap
		$container->addDefinition($this->prefix('robotsRoute'))
			->setClass('Nette\Application\Routers\Route', array('robots.txt',
				array('presenter' => 'Cms:Sitemap', 'action' => 'robots', 'lang' => NULL), Route::SECURED
			))
			->addTag('route', array('priority' => 999999999));
		$container->addDefinition($this->prefix('sitemapRoute'))
			->setClass('Nette\Application\Routers\Route', array('[lang-<lang>/][page-<page>/]sitemap.xml',
				array('presenter' => 'Cms:Sitemap', 'action' => 'sitemap',), Route::SECURED
			))
			->addTag('route', array('priority' => 999999998));

		// Administration
		$container->addDefinition($this->prefix('adminRoute'))
			->setClass('CmsModule\Administration\Routes\Route', array($adminPrefix . '[' . ($adminPrefix ? '/' : '') . '<lang>/]<presenter>[/<action>[/<id>]]',
				array('module' => 'Cms:Admin', 'presenter' => $config['administration']['defaultPresenter'], 'action' => 'default',), Route::SECURED
			))
			->addSetup('injectLanguageRepository', array('@cms.languageRepository'))
			->addSetup('injectPageListener', array('@cms.pageListener'))
			->addSetup('injectDefaultLanguage', array($config['website']['defaultLanguage']))
			->addTag('route', array('priority' => 100000));

		// installation
		if (!$config['administration']['login']['name']) {
			$container->addDefinition($this->prefix('installationRoute'))
				->setClass('Nette\Application\Routers\Route', array('', "Cms:Admin:{$config['administration']['defaultPresenter']}:", Route::ONE_WAY))
				->addTag('route', array('priority' => -1));
		}

		// CMS route
		$container->addDefinition($this->prefix('pageRoute'))
			->setClass('CmsModule\Content\Routes\PageRoute', array('@container', '@cacheStorage', '@doctrine.checkConnectionFactory', $prefix, $parameters, $config['website']['languages'], $config['website']['defaultLanguage'])
			)
			->addTag('route', array('priority' => 100));

		if ($config['website']['oneWayRoutePrefix']) {
			$container->addDefinition($this->prefix('oneWayPageRoute'))
				->setClass('CmsModule\Content\Routes\PageRoute', array('@container', '@cacheStorage', '@doctrine.checkConnectionFactory', $config['website']['oneWayRoutePrefix'], $parameters, $config['website']['languages'], $config['website']['defaultLanguage'], TRUE)
				)
				->addTag('route', array('priority' => 99));
		}

		// File route
		$container->addDefinition($this->prefix('imageRoute'))
			->setClass('CmsModule\Content\Routes\ImageRoute')
			->addTag('route', array('priority' => 99999999));

		$container->addDefinition($this->prefix('fileRoute'))
			->setClass('CmsModule\Content\Routes\FileRoute')
			->addTag('route', array('priority' => 99999990));

		// config manager
		$container->addDefinition($this->prefix('configService'))
			->setClass('CmsModule\Services\ConfigBuilder', array('%configDir%/config.neon'));

		$container->addDefinition($this->prefix('administrationManager'))
			->setClass('CmsModule\Administration\AdministrationManager', array(
				$config['administration']['routePrefix'],
				$config['administration']['defaultPresenter'],
				$config['administration']['login'],
				$config['administration']['theme']
			));

		$container->addDefinition($this->prefix('websiteManager'))
			->setClass('CmsModule\Content\WebsiteManager', array(
				$config['website']['author'],
				$config['website']['defaultLanguage'],
				$config['website']['defaultPresenter'],
				$config['website']['description'],
				$config['website']['errorPresenter'],
				$config['website']['keywords'],
				$config['website']['robots'],
				$config['website']['languages'],
				$config['website']['name'],
				$config['website']['oneWayRoutePrefix'],
				$config['website']['routePrefix'],
				$config['website']['theme'],
				$config['website']['title'],
				$config['website']['titleSeparator']
			));

		// listeners
		$container->addDefinition($this->prefix('fileListener'))
			->setClass('CmsModule\Content\Listeners\FileListener', array(
				'@container',
				$container->parameters['publicDir'] . '/media',
				$container->parameters['dataDir'] . '/media',
				'/public/media',
			))
			->addTag('listener');

		// Structure installators
		$container->addDefinition($this->prefix('administration.structureInstallator'))
			->setClass('CmsModule\Administration\StructureInstallators\StructureInstallator');

		$container->addDefinition($this->prefix('structureInstallatorManager'))
			->setClass('CmsModule\Administration\StructureInstallatorManager')
			->addSetup('registerInstallator', array($this->prefix('@administration.structureInstallator'), 'basic website structure and access\' list'));

		$container->addDefinition($this->prefix('authenticationFormFactory'))
			->setClass('CmsModule\Forms\SystemAuthenticationFormFactory', array($config['administration']['registrations']))
			->addSetup('injectFactory', array('@cms.admin.loggableAjaxFormFactory'));

		$container->addDefinition($this->prefix('admin.loginPresenter'))
			->setClass('CmsModule\Administration\Presenters\LoginPresenter')
			->addSetup('$service->setAutologin(?);', array($config['administration']['authentication']['autologin']))
			->addSetup('$service->setAutoregistration(?);', array($config['administration']['authentication']['autoregistration']))
			->addSetup('$service->setRegistrations(?);', array($config['administration']['registrations']))
			->addSetup('$service->setReset(?);', array($config['administration']['authentication']['forgotPassword']))
			->addTag('presenter');
	}


	public function beforeCompile()
	{
		$this->registerContentTypes();
		$this->registerAdministrationPages();
		$this->registerElements();
		$this->registerUsers();
		$this->registerLoginProvider();
	}


	protected function registerContentTypes()
	{
		$container = $this->getContainerBuilder();
		$manager = $container->getDefinition('cms.contentManager');

		foreach ($container->findByTag('contentType') as $item => $tags) {
			$arguments = $container->getDefinition($item)->factory->arguments;
			$entityName = ltrim($arguments[0], '\\');
			$name = is_array($tags) ? $tags['name'] : $tags;

			$container->getDefinition($item)->factory->arguments = array(
				0 => $name,
				1 => $arguments[0],
			);

			$manager->addSetup('$service->addContentType(?, ?, ?)', $entityName, $name, "@{$item}");
		}
	}


	protected function registerAdministrationPages()
	{
		$container = $this->getContainerBuilder();
		$manager = $container->getDefinition('cms.administrationManager');

		foreach ($this->getSortedServices('administration') as $item) {
			$tags = $container->getDefinition($item)->tags['administration'];
			$manager->addSetup('addAdministrationPage', array($tags['name'], $tags['description'], $tags['category'], $tags['link']));
		}
	}


	protected function registerElements()
	{
		$container = $this->getContainerBuilder();
		$config = $container->getDefinition('venne.widgetManager');

		foreach ($container->findByTag('element') as $factory => $meta) {
			if (!is_string($meta)) {
				throw new \Nette\InvalidArgumentException("Tag element require name. Provide it in configuration. (tags: [element: name])");
			}
			$class = $container->getDefinition(substr($factory, 0, -7))->class;
			$config->addSetup('addWidget', array(\CmsModule\Content\ElementManager::ELEMENT_PREFIX . $meta, $class, "@{$factory}"));
		}
	}


	protected function registerUsers()
	{
		$container = $this->getContainerBuilder();
		$config = $container->getDefinition($this->prefix('securityManager'));

		foreach ($container->findByTag('user') as $item => $tags) {
			$arguments = $container->getDefinition($item)->factory->arguments;

			$container->getDefinition($item)->factory->arguments = array(
				0 => is_array($tags) ? $tags['name'] : $tags,
				1 => $arguments[0],
			);

			$config->addSetup('addUserType', array("@{$item}"));
		}
	}


	protected function registerLoginProvider()
	{
		$container = $this->getContainerBuilder();
		$config = $container->getDefinition($this->prefix('securityManager'));

		foreach ($container->findByTag('loginProvider') as $item => $tags) {
			$class = '\\' . $container->getDefinition($item)->class;
			$type = $class::getType();

			$config->addSetup('addLoginProvider', array($type, "{$item}"));
		}
	}
}
