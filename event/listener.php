<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2014 Lucifer <https://www.anavaro.com>
 * @copyright (c) 2016 kasimi <https://kasimi.net>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\event;

use phpbb\config\config;
use phpbb\controller\helper as controller_helper;
use phpbb\notification\manager;
use phpbb\request\request_interface;
use phpbb\symfony_request;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var user */
	protected $user;

	/** @var template */
	protected $template;

	/** @var manager */
	protected $notification_manager;

	/** @var request_interface */
	protected $request;

	/** @var symfony_request */
	protected $symfony_request;

	/** @var controller_helper */
	protected $controller_helper;

	/**
	 * @return array
	 */
	static public function getSubscribedEvents()
	{
		return [
			'core.acp_board_config_edit_add'	=> 'add_options',
			'core.page_header'					=> 'setup',
		];
	}

	/**
	 * Constructor
	 *
	 * @param config			$config					Config object
	 * @param user				$user					User object
	 * @param template			$template				Template object
	 * @param manager			$notification_manager	Notifications manager
	 * @param request_interface	$request				Request object
	 * @param symfony_request	$symfony_request		Symfony request object
	 * @param controller_helper	$controller_helper		Controller helper
	 */
	public function __construct(
		config $config,
		user $user,
		template $template,
		manager $notification_manager,
		request_interface $request,
		symfony_request $symfony_request,
		controller_helper $controller_helper
	)
	{
		$this->config				= $config;
		$this->user					= $user;
		$this->template				= $template;
		$this->notification_manager	= $notification_manager;
		$this->request				= $request;
		$this->symfony_request		= $symfony_request;
		$this->controller_helper	= $controller_helper;
	}

	/**
	 *
	 */
	public function setup()
	{
		if ($this->user->data['user_id'] != ANONYMOUS && $this->user->data['is_registered'] && !$this->user->data['is_bot'] && $this->config['allow_board_notifications'])
		{
			$last = $this->get_last_notification();

			$this->template->assign_vars([
				'ACTIVE_NOTIFICATIONS_ENABLED'			=> true,
				'ACTIVE_NOTIFICATIONS_LAST'				=> $last,
				'ACTIVE_NOTIFICATIONS_TIME'				=> 1000 * $this->config['notification_pull_time'],
				'ACTIVE_NOTIFICATIONS_SESSION_LENGTH'	=> 1000 * $this->config['session_length'],
				'ACTIVE_NOTIFICATIONS_URL'				=> $this->controller_helper->route('anavaro_activenotifications_puller', [], false),
				'ACTIVE_NOTIFICATIONS_CURRENT_URL'		=> $this->get_current_page(),
				'COOKIE_PREFIX'							=> $this->config['cookie_name'] . '_',
			]);
		}
	}

	/**
	 * @param object $event The event object
	 */
	public function add_options($event)
	{
		if ($event['mode'] == 'features')
		{
			$config = [
				'legend10'					=> 'ACTIVE_NOTIFICATIONS',
				'notification_pull_time'	=> [
					'lang'		=> 'ACTIVE_NOTIFICATIONS_TIME',
					'validate'	=> 'int:5:9999',
					'type'		=> 'number',
					'explain'	=> true,
				],
			];

			$display_vars = $event['display_vars'];
			$display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], $config, ['after' => 'allow_quick_reply']);
			$event['display_vars'] = $display_vars;
		}
	}

	/**
	 * @return int
	 */
	protected function get_last_notification()
	{
		$last_notification = $this->notification_manager->load_notifications('notification.method.board', [
			'limit' => 1,
		]);

		foreach ($last_notification['notifications'] as $notification)
		{
			return (int) $notification->notification_id;
		}

		return 0;
	}

	/**
	 * @return string
	 */
	protected function get_current_page()
	{
		$request_url = $this->symfony_request->getSchemeAndHttpHost() . $this->symfony_request->getBaseUrl() . $this->symfony_request->getPathInfo();
		return rtrim($this->request->escape($request_url, true), '/');
	}
}
