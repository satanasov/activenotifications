<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\event;

class event_failure_test extends \anavaro\activenotifications\tests\event\event_base
{
	/**
	 * @return array
	 */
	public function event_failure_data()
	{
		return array(
			'is_guest' => array(
				1,		// User ID
				true,	// Is registered
				false,	// Is bot
			),

			'is_not_registered' => array(
				2,		// User ID
				false,	// Is registered
				false,	// Is bot
			),

			'is_bot' => array(
				2,		// User ID
				true,	// Is registered
				true,	// Is bot
			),
		);
	}

	/**
	 * @param $user_id
	 * @param $is_registered
	 * @param $is_bot
	 * @dataProvider event_failure_data
	 */
	public function test_event_failure($user_id, $is_registered, $is_bot)
	{
		$this->assertInstanceOf('\anavaro\activenotifications\event\listener', $this->activenotifications_listener);

		$this->set_user_data(array(
			'user_id'			=> $user_id,
			'is_registered'		=> $is_registered,
			'is_bot'			=> $is_bot,
		));

		$this->set_config_data(array(
			'allow_board_notifications' => true,
		));

		$this->template->expects($this->exactly(0))
			->method('assign_vars');

		$this->dispatcher->addListener('core.page_header', array($this->activenotifications_listener, 'setup'));
		$this->dispatcher->dispatch('core.page_header');
	}
}
