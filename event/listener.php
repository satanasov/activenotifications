<?php
/**
*
* Post Love extension for the phpBB Forum Software package.
*
* @copyright (c) 2014 Lucifer <http://www.anavaro.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace anavaro\activenotifications\event;

/**
* Event listener
*/

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\notification\manager */
	protected $notification_manager;

	/** @var \phpbb\controller\helper */
	protected $helper;

	 /** @var \phpbb\request\request */
	protected $request;

	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_board_config_edit_add'	=>	'add_options',
			'core.page_header'		=> 'setup',
		);
	}

	/**
	* Constructor
	* NOTE: The parameters of this method must match in order and type with
	* the dependencies defined in the services.yml file for this service.
	*
	* @param \phpbb\config\config			$config					Config object
	* @param \phpbb\user					$user					User object
	* @param \phpbb\template\template		$template				Template object
	* @param \phpbb\notification\manager	$notification_manager	Notifications manager
	* @param \phpbb\controller\helper		$helper					Controller helper
	* @param \phpbb\request\request			$request				Request object
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\user $user, \phpbb\template\template $template,
	\phpbb\notification\manager $notification_manager, \phpbb\controller\helper $helper, \phpbb\request\request $request)
	{
		$this->config = $config;
		$this->user = $user;
		$this->template = $template;
		$this->notification_manager = $notification_manager;
		$this->helper = $helper;
		$this->request = $request;
	}

	public function setup()
	{
		if ($this->user->data['user_id'] != ANONYMOUS && $this->user->data['is_registered'] && !$this->user->data['is_bot'])
		{
			// Work with cookies
			$cookie = $this->request->variable($this->config['cookie_name'] . '_an', '', true, \phpbb\request\request_interface::COOKIE);

			$last = $this->get_last_notification();
			$last = ($last) ? $last : 0;
			$this->template->assign_vars(array(
				'ACTIVE_NOTIFICATION_LAST'	=> $last,
				'ACTIVE_NOTIFICATION_TIME'	=> $this->config['notification_pull_time'] * 1000,
				'ACTIVE_NOTIFICATION_URL'	=> substr($this->helper->route('notifications_puller', array('last' => $last)), 0, strlen($last) * -1),
				//'ACTIVE_NOTIFICATION_AVATAR_BASE'	=> 	$this->config['server_protocol'] . $this->config['server_name'] . '/download/file.php?avatar=',
				'ACTIVE_NOTIFICATIONS_COOKIE_DOMAIN'	=> $this->config['cookie_domain'],
				'ACTIVE_NOTIFICATIONS_COOKIE_NAME'	=> $this->config['cookie_name'] . '_an',
				'ACTIVE_NOTIFICATIONS_COOKIE_PATH'	=> $this->config['cookie_path'],
			));
		}
	}

	public function add_options($event)
	{
		if ($event['mode'] == 'features')
		{
			// Store display_vars event in a local variable
			$display_vars = $event['display_vars'];
			$my_config_vars = array(
				'legend10'	=> 'ACTIVE_NOTIFICATIONS',
				'notification_pull_time'	=> array('lang' => 'ACTIVE_NOTIFICATIONS_TIME', 'validate' => 'int:0:99', 'type' => 'number', 'explain' => true),
			);
			// Insert my config vars after...
			$insert_after = 'LOAD_CPF_VIEWTOPIC';
			// Rebuild new config var array
			$position = array_search($insert_after, array_keys($display_vars['vars'])) - 1;
			$display_vars['vars'] = array_merge(
				array_slice($display_vars['vars'], 0, $position),
				$my_config_vars,
				array_slice($display_vars['vars'], $position)
			);
			$event['display_vars'] = array('title' => $display_vars['title'], 'vars' => $display_vars['vars']);
		}
	}

	protected function get_last_notification()
	{
		$last_notification = $this->notification_manager->load_notifications(array('limit' => 1));
		foreach ($last_notification['notifications'] as $notification)
		{
			$notifs = $notification->prepare_for_display();
		}
		if (isset($notifs))
		{
			return (int) $notifs['NOTIFICATION_ID'];
		}
		else
		{
			return 0;
		}
	}
}
