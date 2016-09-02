<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\event;

abstract class event_base extends \anavaro\activenotifications\tests\test_base
{
	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\controller\helper $controller_helper */
	protected $controller_helper;

	/** @var \anavaro\activenotifications\event\listener */
	protected $activenotifications_listener;

	public function setUp()
	{
		parent::setUp();

		$this->controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();

		$this->activenotifications_listener = new \anavaro\activenotifications\event\listener(
			$this->config,
			$this->user,
			$this->template,
			$this->notifications,
			$this->controller_helper
		);
	}
}
