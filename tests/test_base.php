<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 Lucifer <https://www.anavaro.com>
 * @copyright (c) 2016 kasimi <https://kasimi.net>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace anavaro\activenotifications\tests;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require_once __DIR__ . '/../../../../../tests/notification/manager_helper.php';

abstract class test_base extends \phpbb_database_test_case
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\notification\manager */
	protected $notifications;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\user */
	protected $user;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\user_loader */
	protected $user_loader;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\auth\auth */
	protected $auth;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\template\template */
	protected $template;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\cache\service */
	protected $cache;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\path_helper */
	protected $path_helper;

	/** @var \phpbb\event\dispatcher_interface */
	protected $dispatcher;

	/** @var \Symfony\Component\DependencyInjection\ContainerInterface */
	protected $container;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/**
	 * @return array List of extensions that should be set up
	 */
	static protected function setup_extensions()
	{
		return ['anavaro/activenotifications'];
	}

	/**
	 * @return \PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/fixtures.xml');
	}

	public function setUp() : void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;
		global $request, $db, $phpbb_dispatcher;

		$this->root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;

		$this->db = $db = $this->new_dbal();

		$this->config = new \phpbb\config\config([]);

		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->language = new \phpbb\language\language($lang_loader);

		$this->user = $this->getMockBuilder('\phpbb\user')
			->disableOriginalConstructor()
			->getMock();

		$this->template = $template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();

		$this->auth = $this->getMockBuilder('\phpbb\auth\auth')
			->getMock();

		$cache_driver = new \phpbb\cache\driver\dummy();

		$this->cache = new \phpbb\cache\service(
			new \phpbb_mock_cache(),
			$this->config,
			$this->db,
			$this->root_path,
			$this->php_ext
		);

		$this->container = $phpbb_container = new ContainerBuilder();

		$this->dispatcher = $phpbb_dispatcher = new \phpbb\event\dispatcher($this->container);

		$this->user_loader = $this->getMockBuilder('\phpbb\user_loader')
			->disableOriginalConstructor()
			->getMock();

		$this->request = $request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();

		$this->path_helper = new \phpbb\path_helper(
			new \phpbb\symfony_request(new \phpbb_mock_request()),
			new \phpbb\filesystem\filesystem(),
			$this->request,
			$phpbb_root_path,
			$phpEx
		);

		$loader = new YamlFileLoader($phpbb_container, new FileLocator(__DIR__ . '/../../../../../tests/notification/fixtures'));
		$loader->load('services_notification.yml');
		$phpbb_container->set('user_loader', $this->user_loader);
		$phpbb_container->set('user', $this->user);
		$phpbb_container->set('language', $this->language);
		$phpbb_container->set('config', $this->config);
		$phpbb_container->set('dbal.conn', $this->db);
		$phpbb_container->set('auth', $this->auth);
		$phpbb_container->set('cache.driver', $cache_driver);
		$phpbb_container->set('cache', $this->cache);
		$phpbb_container->set('text_formatter.utils', new \phpbb\textformatter\s9e\utils());
		$phpbb_container->set('dispatcher', $this->dispatcher);
		$phpbb_container->setParameter('core.root_path', $phpbb_root_path);
		$phpbb_container->setParameter('core.php_ext', $phpEx);
		$phpbb_container->setParameter('tables.notifications', 'phpbb_notifications');
		$phpbb_container->setParameter('tables.user_notifications', 'phpbb_user_notifications');
		$phpbb_container->setParameter('tables.notification_types', 'phpbb_notification_types');

		$this->notifications = new \phpbb_notification_manager_helper(
			[],
			[],
			$this->container,
			$this->user_loader,
			$this->dispatcher,
			$this->db,
			$this->cache,
			$this->language,
			$this->user,
			'phpbb_notification_types',
			'phpbb_user_notifications'
		);

		$phpbb_container->set('notification_manager', $this->notifications);
		$phpbb_container->compile();

		$this->notifications->setDependencies($this->auth, $this->config);

		$types = [];
		foreach ($this->get_notification_types() as $type)
		{
			$class = $this->build_type($type);
			$types[$type] = $class;
		}
		$this->notifications->set_var('notification_types', $types);
		$methods = [];
		foreach ($this->get_notification_methods() as $method)
		{
			$class = $this->container->get($method);
			$methods[$method] = $class;
		}
		$this->notifications->set_var('notification_methods', $methods);
	}

	/**
	 * @param $type
	 * @return object
	 */
	protected function build_type($type)
	{
		return $this->container->get($type);
	}

	/**
	 * @return array
	 */
	protected function get_notification_types()
	{
		return [
			'notification.type.pm'
		];
	}

	/**
	 * @return array
	 */
	protected function get_notification_methods()
	{
		return [
			'notification.method.board',
		];
	}

	/**
	 * @param array $user_data
	 */
	protected function set_user_data($user_data)
	{
		foreach ($user_data as $key => $value)
		{
			$this->user->data[$key] = $value;
		}
	}

	/**
	 * @param array $config_data
	 */
	protected function set_config_data($config_data)
	{
		foreach ($config_data as $key => $value)
		{
			$this->config->set($key, $value);
		}
	}
}
