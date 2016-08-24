<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2015 Lucifer <https://www.anavaro.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\controller;

class controller_test extends \phpbb_database_test_case
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
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
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
	}

	/**
	* Create our controller
	*/
	protected function get_controller($user_id, $is_registered, $ajax)
	{
		global $phpEx, $phpbb_root_path;
		$user = $this->getMock('\phpbb\user', array(), array('\phpbb\datetime'));
		$user->data['user_id'] = $user_id;
		$user->data['is_registered'] = $is_registered;

		$request = $this->getMock('\phpbb\request\request');
		$request->expects($this->any())
			->method('is_ajax')
			->will($this->returnValue($ajax)
		);

		return new \anavaro\activenotifications\controller\main_controller(
			$this->config,
			$user,
			$request,
			$this->phpbb_notifications,
			$this->db,
			$this->template
		);
	}

	// Some issue prevents the test from requesting notifications (Memmory overflows) so we do only 1 valid tests for return
	public function test_controller($user_id = 2, $is_registered = true, $mode = true, $notification = 0, $status_code = 200, $unreads = 0, $new = 0)
	{
		$controller = $this->get_controller($user_id, $is_registered, $mode);
		$response = $controller->base($notification);
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals($status_code, $response->getStatusCode());
		$content = json_decode($response->getContent());
		$this->assertEquals($unreads, $content['unread']);
		$this->assertEquals($new, count($content['notifs']));
	}

	/**
	 * Test data for the test_controller_fails test
	 *
	 * @return array Test data
	 */
	public function controller_fails_data()
	{
		return array(
			array(
				1, // Anonymous
				true, // Anonymous is not a registered user
				true, // Ajax Request
				0,
				403,
				'NO_AUTH_OPERATION',
			),
			array(
				2, // Admin
				false, // Guest is not a registered user
				true,
				0,
				403,
				'NO_AUTH_OPERATION',
			),
			array(
				2, // Admin
				true, // admin is registered user
				false, // Not Ajax request
				0,
				403,
				'NO_AUTH_OPERATION',
			),
		);
	}
	/**
	 * Test the controller throws exceptions under failing conditions
	 * and We test all exceptions!
	 *
	 * @dataProvider controller_fails_data
	 */
	public function test_controller_fails($user_id, $is_registered, $ajax, $notification, $status_code, $content)
	{
		$controller = $this->get_controller($user_id, $is_registered, $ajax);
		try
		{
			$controller->base($notification);
			$this->fail('The expected \phpbb\exception\http_exception was not thrown');
		}
		catch (\phpbb\exception\http_exception $exception)
		{
			$this->assertEquals($status_code, $exception->getStatusCode());
			$this->assertEquals($content, $exception->getMessage());
		}
	}
}
