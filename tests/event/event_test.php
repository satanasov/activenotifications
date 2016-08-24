<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2015 Lucifer <https://www.anavaro.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\event;

class event_test extends \phpbb_database_test_case
{
	/**
	* Define the extensions to be tested
	*
	* @return array vendor/name of extension(s) to test
	*/
	static protected function setup_extensions()
	{
		return array('anavaro/activenotifications');
	}

	protected $db;

	/**
	* Get data set fixtures
	*/
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/users.xml');
	}
	/**
	* Setup test environment
	*/
	public function setUp()
	{
		parent::setUp();

		global $phpbb_dispatcher;
		$this->db = $this->new_dbal();
		$this->phpbb_container = new \phpbb_mock_container_builder();
		$this->config = new \phpbb\config\config(array(
			'notification_pull_time' => 60,
		));
		// TBD
		$auth = $this->getMock('\phpbb\auth\auth');
		$cache = new \phpbb\cache\service(
			new \phpbb\cache\driver\null(),
			$this->config,
			$this->db,
			$phpbb_root_path,
			$phpEx
		);

		$user_loader = new \phpbb\user_loader($this->db, $phpbb_root_path, $phpEx, USERS_TABLE);
		// Event dispatcher
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
		// Notification Types
		$notification_types = array('pm');
		$notification_types_array = array();
		foreach ($notification_types as $type)
		{
			$class_name = '\phpbb\notification\type\\' . $type;
			$class = new $class_name(
				$user_loader, $this->db, $cache->get_driver(), $user, $auth, $this->config,
				$phpbb_root_path, $phpEx,
				NOTIFICATION_TYPES_TABLE, NOTIFICATIONS_TABLE, USER_NOTIFICATIONS_TABLE);
			$this->phpbb_container->set('notification.type.' . $type, $class);
			$notification_types_array['notification.type.' . $type] = $class;
		}
		// Notification Manager
		$this->phpbb_notifications = new \phpbb\notification\manager($notification_types_array, array(),
			$this->phpbb_container, $user_loader, $this->config, $phpbb_dispatcher, $this->db, $cache, $user,
			$phpbb_root_path, $phpEx,
			NOTIFICATION_TYPES_TABLE, NOTIFICATIONS_TABLE, USER_NOTIFICATIONS_TABLE);

		$user = new \phpbb_mock_user;
		$user->optionset('viewcensors', false);
		$user->style['style_path'] = 'prosilver';

		$this->user = $this->getMock('\phpbb\user', array(), array('\phpbb\datetime'));
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('path_helper', $phpbb_path_helper);
		$this->controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMock('\phpbb\request\request');
	}

	// Let's create listener
	protected function set_listener()
	{
		$this->listener = new \anavaro\activenotifications\event\listener(
			$this->config,
			$this->user,
			$this->template,
			$this->phpbb_notifications,
			$this->controller_helper,
			$this->request
		);
	}
	/**
	* Test the event listener is subscribing events
	*/
	public function test_getSubscribedEvents()
	{
		$this->assertEquals(array(
			'core.acp_board_config_edit_add',
			'core.page_header',
		), array_keys(\anavaro\activenotifications\event\listener::getSubscribedEvents()));
	}
	public function setup_data()
	{
		return array(
			'base_case' => array(
				2, //User ID
				true, // Is registered
				false, // Is BOT
				60, // $config['notification_pull_time']
				1, // expected response
				0, // ACTIVE_NOTIFICATION_LAST
				60000, // ACTIVE_NOTIFICATION_TIME
			),
			'change_time' => array(
				2, //User ID
				true, // Is registered
				false, // Is BOT
				10, // $config['notification_pull_time']
				1, // expected response
				0, // ACTIVE_NOTIFICATION_LAST
				10000, // ACTIVE_NOTIFICATION_TIME
			),
			'guest' => array(
				1, //User ID
				true, // Is registered
				false, // Is BOT
				60, // $config['notification_pull_time']
				0, // expected response
				0, // ACTIVE_NOTIFICATION_LAST
				60000, // ACTIVE_NOTIFICATION_TIME
			),
			'not_reged' => array(
				2, //User ID
				false, // Is registered
				false, // Is BOT
				60, // $config['notification_pull_time']
				0, // expected response
				0, // ACTIVE_NOTIFICATION_LAST
				60000, // ACTIVE_NOTIFICATION_TIME
			),
			'is_bot' => array(
				2, //User ID
				true, // Is registered
				true, // Is BOT
				60, // $config['notification_pull_time']
				0, // expected response
				0, // ACTIVE_NOTIFICATION_LAST
				60000, // ACTIVE_NOTIFICATION_TIME
			),
		);
	}
	/**
	 * Test the setup
	 *
	 * @dataProvider setup_data
	 */
	public function test_setup($user_id, $is_reg, $is_bot, $pull_time, $expected, $last_n, $time_n)
	{
		$this->user->data['user_id'] = $user_id;
		$this->user->data['is_registered'] = $is_reg;
		$this->user->data['is_bot'] = $is_bot;
		$this->config['notification_pull_time'] = $pull_time;
		$this->config['enable_mod_rewrite'] = false;
		$this->config['cookie_domain'] = 'localhost';
		$this->config['cookie_name'] = 'phpbb3_asd_obj';
		$this->config['cookie_path'] = '/';
		$this->set_listener();
		if ($expected > 0)
		{
			$this->template->expects($this->exactly($expected))
				->method('assign_vars')
				->with(array(
					'ACTIVE_NOTIFICATION_LAST' => $last_n,
					'ACTIVE_NOTIFICATION_TIME' => $time_n,
					'ACTIVE_NOTIFICATION_URL'	=> false, // This is false becouse we have mock route helper
					'ACTIVE_NOTIFICATIONS_COOKIE_DOMAIN'	=> 'localhost',
					'ACTIVE_NOTIFICATIONS_COOKIE_NAME'	=> 'phpbb3_asd_obj_an',
					'ACTIVE_NOTIFICATIONS_COOKIE_PATH'	=> '/'
				));
		}
		else
		{
			$this->template->expects($this->exactly(0))
				->method('assign_vars');
		}
		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
		$dispatcher->addListener('core.page_header', array($this->listener, 'setup'));
		$dispatcher->dispatch('core.page_header');
	}
}
