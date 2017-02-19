<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\controller;

class controller_success_test extends controller_base
{
	/**
	 * @return array
	 */
	public function controller_data()
	{
		return array(
			array(
				2,		// User ID
				true,	// Is registered
				false,	// Is bot
				true,	// Ajax Request
				0,		// Last notification ID in request
				3,		// Expected last notification ID in response
				2,		// Expected total unread notifications
				3,		// Expected new notifications sent to the client
			),
			array(
				2,		// User ID
				true,	// Is registered
				false,	// Is bot
				true,	// Ajax Request
				1,		// Last notification ID in request
				3,		// Expected last notification ID in response
				2,		// Expected total unread notifications
				2,		// Expected new notifications sent to the client
			),
			array(
				2,		// User ID
				true,	// Is registered
				false,	// Is bot
				true,	// Ajax Request
				2,		// Last notification ID in request
				3,		// Expected last notification ID in response
				2,		// Expected total unread notifications
				1,		// Expected new notifications sent to the client
			),
			array(
				2,		// User ID
				true,	// Is registered
				false,	// Is bot
				true,	// Ajax Request
				3,		// Last notification ID in request
				3,		// Expected last notification ID in response
				2,		// Expected total unread notifications
				0,		// Expected new notifications sent to the client
			),
			array(
				3,		// User ID
				true,	// Is registered
				false,	// Is bot
				true,	// Ajax Request
				0,		// Last notification ID in request
				0,		// Expected last notification ID in response
				0,		// Expected total unread notifications
				0,		// Expected new notifications sent to the client
			),
		);
	}

	/**
	 * @param $user_id
	 * @param $is_registered
	 * @param $is_bot
	 * @param $is_ajax
	 * @param $last_notification_id_in
	 * @param $expected_last_notification_id_out
	 * @param $exptected_unreads
	 * @param $expected_notifications
	 * @dataProvider controller_data
	 */
	public function test_controller($user_id, $is_registered, $is_bot, $is_ajax, $last_notification_id_in, $expected_last_notification_id_out, $exptected_unreads, $expected_notifications)
	{
		$this->assertInstanceOf('\anavaro\activenotifications\controller\main_controller', $this->activenotifications_controller);

		$this->user->data = array_merge($this->user->data, array(
			'user_id'		=> $user_id,
			'is_registered'	=> $is_registered,
			'is_bot'		=> $is_bot,
		));

		$this->set_config_data(array(
			'allow_board_notifications' => true,
		));

		$this->request->expects($this->any())
			->method('is_ajax')
			->will($this->returnValue($is_ajax));

		$this->request->expects($this->exactly(1))
			->method('variable')
			->with('last')
			->will($this->returnValue($last_notification_id_in));

		$this->template->expects($this->exactly($expected_notifications))
			->method('assign_block_vars')
			->with('notifications', $this->anything());

		$response = $this->activenotifications_controller->base();
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);

		$actual = json_decode($response->getContent(), true);
		$this->assertEquals($expected_last_notification_id_out, $actual['last']);
		$this->assertEquals($exptected_unreads, $actual['unread']);
	}
}
