/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 by kasimi
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

jQuery(function($) {

	"use strict";

	var $notificationCount = $('#notification_list_button > strong');
	var lastUnreadCount = parseInt($notificationCount.text(), 10);

	syncedStorage({
		getData: function(accept) {
			var data = {
				last: activeNotifications.lastNotificationId,
				_referer: activeNotifications.currentUrl
			};
			$.post(activeNotifications.updateUrl, data)
				.done(accept)
				.fail(function(jqXHR, textStatus, errorThrown) {
					if (typeof console !== 'undefined' && console.log) {
						console.log('AJAX error. status: ' + textStatus + ', message: ' + errorThrown + ' (' + jqXHR.responseText + ')');
					}
				});
		},
		processData: function(data) {
			$(phpbb).trigger('active_notifications_process_data_before', [data]);

			activeNotifications.lastNotificationId = parseInt(data['last'], 10);

			// Change value of notification counter and set window title
			var newUnreadCount = parseInt(data['unread'], 10);
			if (lastUnreadCount !== newUnreadCount) {
				phpbb.markNotifications($(), newUnreadCount);
				$notificationCount.toggleClass('hidden', !newUnreadCount);
				lastUnreadCount = newUnreadCount;
			}

			// Add notifications
			if (data['notifications']) {
				var $container = $('#notification_list .dropdown-contents > ul');
				$container.find('li.no_notifications').remove();
				$(data['notifications']).find('ul:last').children('li').prependTo($container);
				phpbb.lazyLoadAvatars();
			}

			$(phpbb).trigger('active_notifications_process_data_after', [data, newUnreadCount]);
		},
		updateInterval: activeNotifications.updateInterval,
		sessionLength: activeNotifications.sessionLength,
		storageKeyPrefix: activeNotifications.cookiePrefix + 'active_notifications_'
	});
});
