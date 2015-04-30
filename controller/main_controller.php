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
	public function __construct(\phpbb\config\config $config, \phpbb\user $user, \phpbb\request\request $request, \anavaro\activenotifications\controller\notifyhelper $notifyhelper)
	{
		$this->config = $config;
		$this->user = $user;
		$this->request = $request;
		$this->notifyhelper = $notifyhelper;
	}

	public function base($last)
	{
		if ($this->user->data['user_id'] != ANONYMOUS)
		{
			$this->user->session_begin(false);
			$response = $this->notifyhelper->get_unread($last);
			// Send a JSON response if an AJAX request was used
			if ($this->request->is_ajax())
			{
				return new \Symfony\Component\HttpFoundation\JsonResponse(array(
					$response
				));
			}
			else
			{
				echo '<pre>';
				var_dump($response);
				var_dump($response = $this->notifyhelper->get_last_notification());
				echo '</pre>';
			}
		}
	}
}
