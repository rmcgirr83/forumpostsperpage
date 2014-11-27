<?php
/**
*
* @package phpBB Extension - Forum Posts Per Page
* @copyright (c) 2014 Rich McGirr
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace rmcgirr83\forumpostsperpage\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_manage_forums_request_data'		=> 'acp_manage_forums_request_data',
			'core.acp_manage_forums_initialise_data'	=> 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form'		=> 'acp_manage_forums_display_form',
			'core.acp_manage_forums_validate_data'		=> 'acp_manage_forums_validate_data',
			'core.viewforum_get_topic_data'				=> 'viewforum_get_topic_data',
			/*'core.viewtopic_modify_post_data'			=> 'viewtopic_modify_post_data',*/
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
			'core.search_modify_rowset'					=> 'search_modify_rowset',
			'core.modify_mcp_modules_display_option'	=> 'modify_mcp_modules_display_option',
		);
	}

	// Submit form (add/update)
	public function acp_manage_forums_request_data($event)
	{
		$array = $event['forum_data'];
		$array['forum_posts_per_page'] = $this->request->variable('forum_posts_per_page', 0);
		$event['forum_data'] = $array;
	}

	// Default settings for new forums
	public function acp_manage_forums_initialise_data($event)
	{
		if ($event['action'] == 'add')
		{
			$array = $event['forum_data'];
			$array['forum_posts_per_page'] = (int) 0;
			$event['forum_data'] = $array;
		}
	}

	// ACP forums template output
	public function acp_manage_forums_display_form($event)
	{
		$array = $event['template_data'];
		$array['POSTS_PER_PAGE'] = $event['forum_data']['forum_posts_per_page'];
		$event['template_data'] = $array;
	}

	// validate the input
	public function acp_manage_forums_validate_data($event)
	{
		$errors = $event['errors'];
		$fpp_ary = array(
			array('lang' => 'FORUM_POSTS_PAGE', 'value' => $event['forum_data']['forum_posts_per_page'], 'column_type' => 'USINT:0'),
		);
		validate_range($fpp_ary, $errors);
		$event['errors'] = $errors;
	}

	// modify viewforum and reset config['posts_per_page']
	public function viewforum_get_topic_data($event)
	{
		if (!empty($event['forum_data']['forum_posts_per_page']))
		{
			$this->config->offsetSet('posts_per_page', $event['forum_data']['forum_posts_per_page']);
		}
	}

	// TODO need to find a way to reset config on viewtopic without hard coding...the below does not work
	// modify viewtopic and reset config['posts_per_page']
/*	public function viewtopic_modify_post_data($event)
	{
		if (!empty($event['topic_data']['forum_posts_per_page']))
		{
			$this->config->offsetSet('posts_per_page', $event['topic_data']['forum_posts_per_page']);
		}
	}
*/
	// modify functions display and reset config['posts_per_page']
	public function display_forums_modify_template_vars($event)
	{
		if (!empty($event['row']['forum_posts_per_page']))
		{
			$this->config->offsetSet('posts_per_page', $event['row']['forum_posts_per_page']);
		}
	}

	// modify and reset on search
	public function search_modify_rowset($event)
	{
		$forum_id = $event['rowset'];
		foreach ($forum_id as $row)
		{
			$posts_per_page = $this->get_forum_data($row['forum_id']);
			if (!empty($posts_per_page))
			{
				$this->config->offsetSet('posts_per_page', $posts_per_page);
			}
		}
	}

	// modify and reset mcp
	public function modify_mcp_modules_display_option($event)
	{
		if ($event['mode'] == 'topic_view')
		{
			$posts_per_page = $this->get_forum_data($event['forum_id']);
			if (!empty($posts_per_page))
			{
				$this->config->offsetSet('posts_per_page', $posts_per_page);
			}
		}
	}

	// get forum posts per page and return value
	private function get_forum_data($forum_id)
	{
		$sql = 'SELECT forum_posts_per_page
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql);
		$posts_per_page = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $posts_per_page['forum_posts_per_page'];
	}
}
