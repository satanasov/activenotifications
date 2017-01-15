<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests;

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
		return array('anavaro/activenotifications');
	}

	/**
	 * @return \PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/fixtures.xml');
	}

	public function setUp()
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;
		global $request, $phpbb_dispatcher;

		$this->root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;

		$this->db = $this->new_dbal();

		$this->config = new \phpbb\config\config(array());

		$this->user = $this->getMock('\phpbb\user', array(), array(
			new \phpbb\language\language(new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx)),
			'\phpbb\datetime'));

		$this->template = $template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();

		$this->auth = $this->getMockBuilder('\phpbb\auth\auth')
			->getMock();

		$this->cache = new \phpbb\cache\service(
			new \phpbb_mock_cache(),
			$this->config,
			$this->db,
			$this->root_path,
			$this->php_ext
		);

		$this->container = new \phpbb_mock_container_builder();

		$this->dispatcher = $phpbb_dispatcher = new \phpbb\event\dispatcher($this->container);

		$this->user_loader = $this->getMockBuilder('\phpbb\user_loader')
			->disableOriginalConstructor()
			->getMock();

		$this->request = $request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();

		$this->path_helper = new \phpbb\path_helper(
			new \phpbb\symfony_request(new \phpbb_mock_request()),
			new \phpbb\filesystem(),
			$this->request,
			$phpbb_root_path,
			$phpEx
		);

		$this->notifications = new \phpbb_notification_manager_helper(
			array(),
			array(),
			$this->container,
			$this->user_loader,
			$this->config,
			$this->dispatcher,
			$this->db,
			$this->cache,
			$this->user,
			$this->root_path,
			$this->php_ext,
			NOTIFICATION_TYPES_TABLE,
			NOTIFICATIONS_TABLE,
			USER_NOTIFICATIONS_TABLE
		);

		$this->notifications->setDependencies($this->auth, $this->config);
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
