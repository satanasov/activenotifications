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

class main_controller
{
	public function __construct(\phpbb\config\config $config, \phpbb\user $user, \phpbb\request\request $request, \phpbb\notification\manager $notification_manager,
	\phpbb\db\driver\driver_interface $db)
	{
		$this->config = $config;
		$this->user = $user;
		$this->request = $request;
		$this->notification_manager = $notification_manager;
		$this->db = $db;
	}

	public function base($last)
	{
		//$this->user->session_begin(false);
		if ($this->user->data['user_id'] != ANONYMOUS && $this->user->data['is_registered'] == true && $this->user->data['is_bot'] == false)
		{
			$this->user->session_begin(false);
			$response = $this->get_unread($last);
			// Send a JSON response if an AJAX request was used
		}
		else
		{
			throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
		}

		if ($this->request->is_ajax())
		{
			$this->user->session_begin(false);
			return new \Symfony\Component\HttpFoundation\JsonResponse(array(
				$response
			));
		}
		else
		{
			var_dump($response);
			//throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
		}
	}
	protected function get_unread($last)
	{
		$this->user->session_begin(false);
		$notifications_new = array();
		$sql = 'SELECT notification_id FROM ' . NOTIFICATIONS_TABLE . '
		WHERE notification_id > ' . $last . ' AND user_id = ' . $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$notifications_new[] = (int) $row['notification_id'];
		}
		$this->db->sql_freeresult($result);
		$notifications = $this->notification_manager->load_notifications(array(
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
				if($tmp['U_MARK_READ'] != '')
				{
					$mark = explode('?', $tmp['U_MARK_READ']);
					$tmp['U_MARK_READ'] = $this->config['server_protocol'] . $this->config['server_name'] . '/index.php?' . $mark[1];
				}
				if ($tmp['URL'] != '')
				{
					$url = explode('/', $tmp['URL']);
					if ($url[0] == '.')
					{
						foreach($url as $id => $el)
						{
							if ($el == '.' || $el == '..')
							{
								unset($url[$id]);
							}
						}
						$tmp['URL'] = $this->config['server_protocol'] . $this->config['server_name'] . '/' . implode('/', $url);
					}
				}
				$output['notifs'][] = $tmp;
			}
		}
		return $output;
	}
}
