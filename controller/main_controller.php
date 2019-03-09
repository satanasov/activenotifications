<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2015 Lucifer <https://www.anavaro.com>
 * @copyright (c) 2016 kasimi <https://kasimi.net>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\controller;

use phpbb\config\config;
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\exception\http_exception;
use phpbb\notification\manager;
use phpbb\notification\type\type_interface;
use phpbb\path_helper;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\HttpFoundation\JsonResponse;

class main_controller
{
	/** @var user */
	protected $user;

	/** @var config */
	protected $config;

	/** @var request_interface */
	protected $request;

	/** @var manager */
	protected $notification_manager;

	/** @var string */
	protected $notifications_table;

	/** @var db_interface */
	protected $db;

	/** @var template */
	protected $template;

	/** @var path_helper */
	protected $path_helper;

	/**
	 * Constructor
	 *
	 * @param user				$user
	 * @param config			$config
	 * @param request_interface	$request
	 * @param manager			$notification_manager
	 * @param string			$notifications_table
	 * @param db_interface		$db
	 * @param template			$template
	 * @param path_helper		$path_helper
	 */
	public function __construct(
		user $user,
		config $config,
		request_interface $request,
		manager $notification_manager,
		$notifications_table,
		db_interface $db,
		template $template,
		path_helper $path_helper
	)
	{
		$this->user					= $user;
		$this->config				= $config;
		$this->request				= $request;
		$this->notification_manager	= $notification_manager;
		$this->notifications_table	= $notifications_table;
		$this->db					= $db;
		$this->template				= $template;
		$this->path_helper			= $path_helper;
	}

	/**
	 * @return JsonResponse
	 */
	public function base()
	{
		if ($this->user->data['user_id'] == ANONYMOUS || !$this->user->data['is_registered'] || $this->user->data['is_bot'] || !$this->request->is_ajax() || !$this->config['allow_board_notifications'])
		{
			throw new http_exception(403, 'NO_AUTH_OPERATION');
		}

		$last = $this->request->variable('last', 0);

		$notifications_content = '';
		$notifications = $this->get_unread($last);

		if (!empty($notifications['notifications']))
		{
			$this->template->assign_var('T_THEME_PATH', generate_board_url() . '/styles/' . rawurlencode($this->user->style['style_path']) . '/theme');

			foreach ($notifications['notifications'] as $notification)
			{
				$last = max($last, $notification->notification_id);

				/** @var type_interface $notification */
				$notification_for_display = $notification->prepare_for_display();
				$notification_for_display['URL'] = $this->relative_to_absolute_url($notification_for_display['URL']);
				$notification_for_display['U_MARK_READ'] = $this->relative_to_absolute_url($notification_for_display['U_MARK_READ']);

				$this->template->assign_block_vars('notifications', $notification_for_display);
			}

			$notifications_content = $this->render_template('notification_dropdown.html');
		}

		return new JsonResponse(array(
			'last'			=> $last,
			'unread'		=> $notifications['unread_count'],
			'notifications'	=> $notifications_content,
		));
	}

	/**
	 * @param int $last
	 * @return array
	 */
	protected function get_unread($last)
	{
		$notifications_new = array();

		$sql = 'SELECT notification_id
			FROM ' . $this->notifications_table . '
			WHERE notification_id > ' . (int) $last . '
				AND user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$notifications_new[] = (int) $row['notification_id'];
		}
		$this->db->sql_freeresult($result);

		// Add non-existent notification so that no new notifications are returned
		if (!$notifications_new)
		{
			$notifications_new[] = 0;
		}

		return $this->notification_manager->load_notifications('notification.method.board', array(
			'notification_id'	=> $notifications_new,
			'count_unread'		=> true,
		));
	}

	/**
	 * Renders a template file and returns it
	 *
	 * @param string $template_file
	 * @return string
	 */
	protected function render_template($template_file)
	{
		$this->template->set_filenames(array('body' => $template_file));
		$content = $this->template->assign_display('body', '', true);

		return trim(str_replace(array("\r", "\n"), '', $content));
	}

	/**
	 * Removes all ../ from the beginning of the $url and prepends the board url.
	 *
	 * Example
	 *  in: "./../index.php"
	 *  out: "http://example-board.net/index.php"
	 *
	 * @param string $url
	 * @return string
	 */
	protected function relative_to_absolute_url($url)
	{
		if (!$url)
		{
			return '';
		}

		// Remove leading ../
		$url = $this->path_helper->remove_web_root_path($url);

		// Remove leading . if present
		if (strlen($url) && $url[0] === '.')
		{
			$url = substr($url, 1);
		}

		// Prepend / if not present
		if (strlen($url) && $url[0] !== '/')
		{
			$url = '/' . $url;
		}

		// Prepend board url
		$url = generate_board_url() . $url;

		return $url;
	}
}
