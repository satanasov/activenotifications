<?php
/**
*
* Active Notifications for the phpBB Forum Software package.
*
* @copyright (c) 2015 Lucifer <http://www.anavaro.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace anavaro\activenotifications\controller;

use Symfony\Component\DependencyInjection\Container;

/**
* Admin controller
*/
class notifyhelper
{

	public function __construct(\phpbb\config\config $config, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, Container $phpbb_container)
	{
		$this->config = $config;
		$this->user = $user;
		$this->db = $db;
		$this->phpbb_container = $phpbb_container;
		
		$this->notifications = $this->phpbb_container->get('notification_manager');
	}

	public function get_last_notification()
	{
		$notifications = $this->phpbb_container->get('notification_manager');
		$last_notification = $notifications->load_notifications(array('limit' => 1));
		foreach ($last_notification['notifications'] as $notification)
		{
			$notifs = $notification->prepare_for_display();
		}
		return (int) $notifs['NOTIFICATION_ID'];
	}
	public function get_unread($last)
	{
		$notifications_new = array();
		$sql = 'SELECT notification_id FROM ' . NOTIFICATIONS_TABLE . '
		WHERE notification_id > ' . $last . ' AND user_id = ' . $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$notifications_new[] = (int) $row['notification_id'];
		}
		$phpbb_notifications = $this->phpbb_container->get('notification_manager');
		$notifications = $phpbb_notifications->load_notifications(array(
			'notification_id'	=> $notifications_new,
			'count_unread'	=> true,
		));
		$output = array();
		$output['unread'] = $notifications['unread_count'];
		if (!empty($notifications_new))
		{
			foreach ($notifications['notifications'] as $notification)
			{
				$tmp = $notification->prepare_for_display();
				$output['notifs'][] = $tmp;
			}
		}
		return $output;
	}
}
