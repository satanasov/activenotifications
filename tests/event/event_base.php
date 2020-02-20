<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 Lucifer <https://www.anavaro.com>
 * @copyright (c) 2016 kasimi <https://kasimi.net>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\event;

abstract class event_base extends \anavaro\activenotifications\tests\test_base
{
	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\controller\helper $controller_helper */
	protected $controller_helper;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\symfony_request $symfony_request */
	protected $symfony_request;

	/** @var \anavaro\activenotifications\event\listener */
	protected $activenotifications_listener;

	public function setUp() : void
	{
		parent::setUp();

		$this->controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();

		$this->symfony_request = $this->getMockBuilder('\phpbb\symfony_request')
			->disableOriginalConstructor()
			->getMock();

		$this->activenotifications_listener = new \anavaro\activenotifications\event\listener(
			$this->config,
			$this->user,
			$this->template,
			$this->notifications,
			$this->request,
			$this->symfony_request,
			$this->controller_helper
		);
	}
}
