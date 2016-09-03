<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\tests\controller;

require_once __DIR__ . '/../../../../../includes/functions.php';

abstract class controller_base extends \anavaro\activenotifications\tests\test_base
{
	/** @var \anavaro\activenotifications\controller\main_controller */
	protected $activenotifications_controller;

	public function setUp()
	{
		parent::setUp();

		$this->activenotifications_controller = new \anavaro\activenotifications\controller\main_controller(
			$this->user,
			$this->request,
			$this->notifications,
			$this->db,
			$this->template,
			$this->path_helper
		);
	}
}
