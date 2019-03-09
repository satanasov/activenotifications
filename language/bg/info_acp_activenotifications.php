<?php
/**
*
* Active Notifications [Bulgarian]
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
	$lang = [];
}
$lang = array_merge($lang, [
	'ACTIVE_NOTIFICATIONS'	=> 'Активни нотификации',
	'ACTIVE_NOTIFICATIONS_TIME'	=> 'Интервал за заявка',
	'ACTIVE_NOTIFICATIONS_TIME_EXPLAIN'	=> 'През колко секунди да проверява за нови нотификации? <br/> ВНИМАНИЕ! Колкото по кратък е интервала, толкова повече заявки ще прави всеки клиент към сървъра ви.',
]);
