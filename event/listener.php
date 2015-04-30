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
	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_board_config_edit_add'	=>	'add_options',
			'core.user_setup'		=> 'setup',
		);
	}

	/**
	* Constructor
	* NOTE: The parameters of this method must match in order and type with
	* the dependencies defined in the services.yml file for this service.
	*
	* @param \phpbb\config										$config		Config object
	* @param \phpbb\user										$user		User object
	* @param \phpbb\template									$template	Template object
	* @param \phpbb\activenotifications\controller\notifyhelper	$notifyhelper	notifications helper
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\user $user, \phpbb\template\template $template, \phpbb\request\request $request, \anavaro\activenotifications\controller\notifyhelper $notifyhelper)
	{
		$this->config = $config;
		$this->user = $user;
		$this->template = $template;
		$this->request = $request;
		$this->notifyhelper = $notifyhelper;
	}

	public function setup()
	{
		if ($this->user->data['user_id'] != ANONYMOUS)
		{
			$last = $this->notifyhelper->get_last_notification();
			$this->template->assign_vars(array(
				'ACTIVE_NOTIFICATION_LAST'	=> ($last) ? $last : 0,
				'ACTIVE_NOTIFICATION_TIME'	=> $this->config['notification_pull_time'] * 1000,
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
}