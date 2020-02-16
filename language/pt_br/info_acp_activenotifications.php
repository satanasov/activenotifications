<?php
/**
*
* Active Notifications [Brazilian Portuguese [pt_br]]
* Brazilian Portuguese translation by eunaumtenhoid (c) 2017 [ver 0.9.0] (https://github.com/phpBBTraducoes)
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
	'ACTIVE_NOTIFICATIONS'	=> 'Notificações ativas',
	'ACTIVE_NOTIFICATIONS_TIME'	=> 'Intervalo de tração',
	'ACTIVE_NOTIFICATIONS_TIME_EXPLAIN'	=> 'Quantos segundos o script deve aguardar entre as solicitações?<br /> AVISO! Intervalo mais baixo significa mais pedidos de usuários!',
));
