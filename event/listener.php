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
use phpbb\path_helper;
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

	/** @var controller_helper */
	protected $controller_helper;

	/** @var path_helper */
	protected $path_helper;

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
	 * @param controller_helper	$controller_helper		Controller helper
	 * @param path_helper		$path_helper			Path helper
	 */
	public function __construct(
		config $config,
		user $user,
		template $template,
		manager $notification_manager,
		controller_helper $controller_helper,
		path_helper $path_helper
	)
	{
		$this->config				= $config;
		$this->user					= $user;
		$this->template				= $template;
		$this->notification_manager	= $notification_manager;
		$this->controller_helper	= $controller_helper;
		$this->path_helper			= $path_helper;
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
			// Store display_vars event in a local variable
			$display_vars = $event['display_vars'];
			$my_config_vars = [
				'legend10'					=> 'ACTIVE_NOTIFICATIONS',
				'notification_pull_time'	=> ['lang' => 'ACTIVE_NOTIFICATIONS_TIME', 'validate' => 'int:5:9999', 'type' => 'number', 'explain' => true],
			];

			// Insert my config vars after...
			$insert_after = 'LOAD_CPF_VIEWTOPIC';

			// Rebuild new config var array
			$position = array_search($insert_after, array_keys($display_vars['vars'])) - 1;
			$display_vars['vars'] = array_merge(
				array_slice($display_vars['vars'], 0, $position),
				$my_config_vars,
				array_slice($display_vars['vars'], $position)
			);

			$event['display_vars'] = ['title' => $display_vars['title'], 'vars' => $display_vars['vars']];
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
		$page = $this->user->page['page_name'];

		// Remove app.php if URL rewriting is enabled in the ACP
		if ($this->config['enable_mod_rewrite'])
		{
			$app_php = 'app.' . $this->path_helper->get_php_ext() . '/';

			if (($app_position = strpos($page, $app_php)) !== false)
			{
				$page = substr($page, $app_position + strlen($app_php));
			}
		}

		return generate_board_url() . '/' . $page;
	}
}
