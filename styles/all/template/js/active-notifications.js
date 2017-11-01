/**
 *
 * @package phpBB Extension - Active Notifications
 * @copyright (c) 2016 by kasimi
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

jQuery(function($) {

	"use strict";

	var lastUnreadCount = parseInt($('strong', '#notification_list_button').html());

	syncedStorage({
		getData: function(accept) {
			$.getJSON(activeNotifications.updateUrl, {last: activeNotifications.lastNotificationId})
				.done(accept)
				.fail(function(jqXHR, textStatus, errorThrown) {
					if (typeof console !== 'undefined' && console.log) {
						console.log('AJAX error. status: ' + textStatus + ', message: ' + errorThrown + ' (' + jqXHR.responseText + ')');
					}
				});
		},
		processData: function(data) {
			activeNotifications.lastNotificationId = parseInt(data['last']);

			// Change value of notification counter and set window title
			var newUnreadCount = parseInt(data['unread']);
			if (lastUnreadCount !== newUnreadCount) {
				phpbb.markNotifications($(), newUnreadCount);
				$('#notification_list_button > strong').toggleClass('hidden', !newUnreadCount);
				lastUnreadCount = newUnreadCount;
			}

			// Add notifications
			if (data['notifications']) {
				$(data['notifications']).find('ul:last').children('li').insertBefore('.dropdown-contents ul li:first');
				phpbb.lazyLoadAvatars();
			}
		},
		updateInterval: activeNotifications.updateInterval,
		sessionLength: activeNotifications.sessionLength,
		storageKeyPrefix: activeNotifications.cookiePrefix + 'active_notifications_'
	});
});
