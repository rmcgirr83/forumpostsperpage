<?php
/**
*
* Forum Posts Per Page [English]
*
* @package language Forum Posts Per Page
* @copyright (c) 2014 RMcGirr83
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
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
	// ACP
	'FORUM_POSTS_PAGE'		=> 'Forum Posts Per Page',
	'FORUM_POSTS_PAGE_EXPLAIN'		=> 'The number of posts to display in a topic for this forum.  If set to 0, the setting will be ignored.',
));
