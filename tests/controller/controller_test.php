<?php
/**
*
* Advanced Board Announcements extension for the phpBB Forum Software package.
*
* @copyright (c) 2015 Lucifer <https://www.anavaro.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace anavaro\activenotifications\tests\controller;

require_once dirname(__FILE__) . '/../../../../../includes/functions.php';

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

		$this->db = $this->new_dbal();
	}

	/**
	* Create our controller
	*/
	protected function get_controller($user_id, $is_registered, $ajax)
	{
		global $config, $phpbb_container, $phpEx, $phpbb_root_path, $phpbb_notifications, $phpbb_dispatcher;
		$phpbb_container = new \phpbb_mock_container_builder();
	//	$phpbb_container->set('cache.driver', new \phpbb\cache\driver\null());
		$this->phpbb_container = $phpbb_container;
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
			$phpbb_container->set('notification.type.' . $type, $class);
			$notification_types_array['notification.type.' . $type] = $class;
		}
		// Notification Manager
		$phpbb_notifications = new \phpbb\notification\manager($notification_types_array, array(),
			$phpbb_container, $user_loader, $this->config, $phpbb_dispatcher, $this->db, $cache, $user,
			$phpbb_root_path, $phpEx,
			NOTIFICATION_TYPES_TABLE, NOTIFICATIONS_TABLE, USER_NOTIFICATIONS_TABLE);
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
			$phpbb_notifications,
			$this->db
		);
	}

	public function test_controller()
	{
		global $phpbb_notifications;
		$controller = $this->get_controller(2, true, true);
		$data = array(
			'msg_id'				=> 1,
			'from_user_id'			=> 1,
			'message_subject'		=> 'test',
			'recipients'			=> array(2 => ''),
		);
		$phpbb_notifications->add_notifications('notification.type.pm', $data);

		$response = $controller->base(0);
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals(200, $response->getStatusCode());
		$content = $response->getContent();
		$this->assertContains('zazazaz', $content);
		$this->assertEquals(1, $content['unread']);
		$this->assertEquals(1, count($content['notifs']));
	}
/*	public function test_controller($announce_id, $user_id, $is_registered, $ajax, $status_code, $content, $expected)
	{
		$controller = $this->get_controller($user_id, $is_registered, $ajax);

		$response = $controller->close($announce_id);
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals($status_code, $response->getStatusCode());
		$this->assertEquals($content, $response->getContent());
		$this->assertEquals($expected, $this->check_board_announcement_status($user_id, $announce_id));
	}
	
	/**
	 * Test data for the test_controller_fails test
	 *
	 * @return array Test data
	 */
/*	public function controller_fails_data()
	{
		return array(
			array(
				1,
				false, // Guest is not a registered user
				'foobar', // Invalid hash
				true,
				1,
				403,
				'NO_AUTH_OPERATION',
			),
			array(
				1,
				false, // Guest is not a registered user
				'', // Empty hash
				true,
				1,
				403,
				'NO_AUTH_OPERATION',
			),
			array(
				1,
				false, // Guest is not a registered user
				'close_boardannouncement',
				true,
				2, // Board Announcement can not be aknowledged
				403,
				'NO_AUTH_OPERATION',
			),
		);
	}
	/**
	 * Test the controller throws exceptions under failing conditions
	 *
	 * @dataProvider controller_fails_data
	 */
/*	public function test_controller_fails($user_id, $is_registered, $mode, $ajax, $announce_id, $status_code, $content)
	{
		$controller = $this->get_controller($user_id, $is_registered, $mode, $ajax);
		try
		{
			$controller->close($announce_id);
			$this->fail('The expected \phpbb\exception\http_exception was not thrown');
		}
		catch (\phpbb\exception\http_exception $exception)
		{
			$this->assertEquals($status_code, $exception->getStatusCode());
			$this->assertEquals($content, $exception->getMessage());
		}
	}
	
	/**
	 * Helper to get the stored board announcement status for a user
	 *
	 * @param $user_id
	 * @return int
	 */
/*	protected function check_board_announcement_status($user_id, $announce_id)
	{
		$sql = 'SELECT announce_akn
			FROM phpbb_users
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$status = $this->db->sql_fetchfield('announce_akn');
		$this->db->sql_freeresult($result);
		$akn = explode(':', $status);
		$response = in_array($announce_id, $akn);
		return $response;
	}*/
}