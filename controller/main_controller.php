<?php

/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2015 Lucifer <https://www.anavaro.com>
 * @copyright (c) 2016 kasimi
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anavaro\activenotifications\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use phpbb\exception\http_exception;

class main_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\notification\manager */
	protected $notification_manager;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config				$config
	 * @param \phpbb\user						$user
	 * @param \phpbb\request\request			$request
	 * @param \phpbb\notification\manager		$notification_manager
	 * @param \phpbb\db\driver\driver_interface	$db
	 * @param \phpbb\template\template			$template
	 */
	public function __construct(
		\phpbb\config\config					$config,
		\phpbb\user								$user,
		\phpbb\request\request					$request,
		\phpbb\notification\manager				$notification_manager,
		\phpbb\db\driver\driver_interface		$db,
		\phpbb\template\template				$template
	)
	{
		$this->config							= $config;
		$this->user								= $user;
		$this->request							= $request;
		$this->notification_manager				= $notification_manager;
		$this->db								= $db;
		$this->template							= $template;
	}

	/**
	 * @param int $last
	 * @return JsonResponse
	 */
	public function base($last)
	{
		if ($this->user->data['user_id'] == ANONYMOUS || !$this->user->data['is_registered'] || $this->user->data['is_bot'] || !$this->request->is_ajax())
		{
			throw new http_exception(403, 'NO_AUTH_OPERATION');
		}

		$last = (int) $last;

		// Fix avatars & smilies
		if (!defined('PHPBB_USE_BOARD_URL_PATH'))
		{
			define('PHPBB_USE_BOARD_URL_PATH', true);
		}

		$notifications_content = '';
		$notifications = $this->get_unread($last);

		if (!empty($notifications['notifications']))
		{
			$this->template->assign_vars(array(
				'T_THEME_PATH' => generate_board_url() . '/styles/' . rawurlencode($this->user->style['style_path']) . '/theme',
			));

			foreach ($notifications['notifications'] as $notification)
			{
				$last = max($last, $notification->notification_id);
				$this->template->assign_block_vars('notifications', $notification->prepare_for_display());
			}

			$notifications_content = $this->render_template('notifications.html');
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
			FROM ' . NOTIFICATIONS_TABLE . '
			WHERE notification_id > ' . (int) $last . '
				AND user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$notifications_new[] = (int) $row['notification_id'];
		}
		$this->db->sql_freeresult($result);

		// Ad non-existent notification so that no new notifications are returned
		if (!$notifications_new)
		{
			$notifications_new[] = 0;
		}

		return $this->notification_manager->load_notifications(array(
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
	public function render_template($template_file)
	{
		$this->template->set_filenames(array('body' => $template_file));
		$content = $this->template->assign_display('body', '', true);

		return trim(str_replace(array("\r", "\n"), '', $content));
	}
}
