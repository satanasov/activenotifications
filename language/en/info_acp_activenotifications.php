<?php
/**
*
* Active Notifications [English]
*
* @package language
* @version $Id$
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}
$lang = array_merge($lang, array(
	'ACTIVE_NOTIFICATIONS'	=> 'Active notifikcations',
	'ACTIVE_NOTIFICATIONS_TIME'	=> 'Pull interval',
	'ACTIVE_NOTIFICATIONS_TIME_EXPLAIN'	=> 'How many seconds should the script wait between requests? <br/> WARNING! Lower interval means more user requests!',
));
