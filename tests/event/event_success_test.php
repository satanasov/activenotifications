<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\event;

class event_success_test extends \anavaro\activenotifications\tests\event\event_base
{
	/**
	 *
	 */
	public function test_getSubscribedEvents()
	{
		$expected_events = array(
			'core.acp_board_config_edit_add',
			'core.page_header',
		);

		$actual_events = array_keys(\anavaro\activenotifications\event\listener::getSubscribedEvents());

		$this->assertEquals($expected_events, $actual_events);
	}

	/**
	 * @return array
	 */
	public function event_success_data()
	{
		return array(
			'base_case' => array(
				2,		// User ID
				true,	// Is registered
				false,	// Is bot
				60,		// $config['notification_pull_time']
				3600,	// $config['session_length']
				3,		// Expected last notification ID
			),

			'change_time' => array(
				2,		// User ID
				true,	// Is registered
				false,	// Is bot
				10,		// $config['notification_pull_time']
				600,	// $config['session_length']
				3,		// Expected last notification ID
			),
		);
	}

	/**
	 * @param $user_id
	 * @param $is_registered
	 * @param $is_bot
	 * @param $cfg_notification_pull_time
	 * @param $cfg_session_length
	 * @param $expected_last_id
	 * @dataProvider event_success_data
	 */
	public function test_event_success($user_id, $is_registered, $is_bot, $cfg_notification_pull_time, $cfg_session_length, $expected_last_id)
	{
		$this->assertInstanceOf('\anavaro\activenotifications\event\listener', $this->activenotifications_listener);

		$this->set_user_data(array(
			'user_id'					=> $user_id,
			'is_registered'				=> $is_registered,
			'is_bot'					=> $is_bot,
		));

		$this->set_config_data(array(
			'notification_pull_time'	=> $cfg_notification_pull_time,
			'session_length'			=> $cfg_session_length,
			'cookie_name'				=> 'phpbb_active_notifications_test'
		));

		$this->template->expects($this->exactly(1))
			->method('assign_vars')
			->with(array(
				'ACTIVE_NOTIFICATIONS_LAST'				=> $expected_last_id,
				'ACTIVE_NOTIFICATIONS_TIME'				=> 1000 * $cfg_notification_pull_time,
				'ACTIVE_NOTIFICATIONS_SESSION_LENGTH'	=> 1000 * $cfg_session_length,
				'ACTIVE_NOTIFICATIONS_URL'				=> null,
				'A_COOKIE_PREFIX'						=> 'phpbb_active_notifications_test_',
			));

		$this->dispatcher->addListener('core.page_header', array($this->activenotifications_listener, 'setup'));
		$this->dispatcher->dispatch('core.page_header');
	}
}
