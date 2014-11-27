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

	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\user */
	protected $user;

	public function __construct(\phpbb\cache\service $cache, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\user $user)
	{
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->user = $user;
	}

	static public function getSubscribedEvents()
	{
		return array(
			// ACP activities
			'core.acp_manage_forums_request_data'		=> 'acp_manage_forums_request_data',
			'core.acp_manage_forums_initialise_data'	=> 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form'		=> 'acp_manage_forums_display_form',
			'core.acp_manage_forums_validate_data'		=> 'acp_manage_forums_validate_data',
			'core.acp_manage_forums_update_data_after'	=> 'acp_manage_forums_update_data_after',
			// forum changes to config[posts_per_page]
			'core.viewforum_get_topic_data'				=> 'viewforum_get_topic_data',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
			'core.search_modify_rowset'					=> 'search_modify_rowset',
			'core.modify_mcp_modules_display_option'	=> 'modify_mcp_modules_display_option',
			'core.user_setup'							=> 'user_setup',
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

	// purge our cache after forums are updated
	public function acp_manage_forums_update_data_after($event)
	{
		$this->cache->purge('_forum_posts_per_page');
	}

	// modify viewforum and reset config['posts_per_page']
	public function viewforum_get_topic_data($event)
	{
		if (!empty($event['forum_data']['forum_posts_per_page']))
		{
			$this->posts_per_page($event['forum_data']['forum_posts_per_page']);
		}
	}

	// modify functions display and reset config['posts_per_page']
	public function display_forums_modify_template_vars($event)
	{
		if (!empty($event['row']['forum_posts_per_page']))
		{
			$this->posts_per_page($event['row']['forum_posts_per_page']);
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
				$this->posts_per_page($posts_per_page);
			}
		}
	}

	// modify and reset mcp
	public function modify_mcp_modules_display_option($event)
	{
		if ($event['mode'] == 'topic_view' || $event['mode'] == 'forum_view')
		{
			$posts_per_page = $this->get_forum_data($event['forum_id']);
			if (!empty($posts_per_page))
			{
				$this->posts_per_page($posts_per_page);
			}
		}
	}

	// check what page the user is on and the query string as well
	public function user_setup($event)
	{
		$page_name = substr($this->user->page['page_name'], 0, strpos($this->user->page['page_name'], '.'));

		//we only care about viewtopic all others are handled by events
		if ($page_name == 'viewtopic')
		{
			$string = $this->user->page['query_string'];
			$string = parse_str($string, $output);

			foreach ($output as $key => $value)
			{
				if ($key == 'p' || $key == 't')
				{
					$posts_per_page = $this->get_forum_id_from_table($key, $value);
				}
				else if ($key == 'f')
				{
					$posts_per_page = $this->get_forum_data($value);
				}
				if (!empty($posts_per_page))
				{
					$this->posts_per_page($posts_per_page);
				}
			}
		}
	}

	// change posts_per_page
	private function posts_per_page($data)
	{
		$this->config->offsetSet('posts_per_page', $data);
	}

	// need to retrieve the forum_id from the table
	private function get_forum_id_from_table($key, $value)
	{
		$sql_from = 'FROM ' . TOPICS_TABLE;
		$sql_where = 'WHERE topic_id = ' . $value;

		if ($key == 'p')
		{
			$sql_from = 'FROM ' . POSTS_TABLE;
			$sql_where = 'WHERE post_id = ' . $value;
		}
		$sql = 'SELECT forum_id
			' . $sql_from . '
			' . $sql_where;
		$result = $this->db->sql_query($sql);
		$temp = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// now check the cache for an entry
		$temp = $this->get_forum_data($temp['forum_id']);
		return $temp;
	}

	// get forum posts per page and set the cache
	private function get_forum_data($forum_id)
	{
		if (($posts_per_page = $this->cache->get('_forum_posts_per_page')) === false)
		{
			// we only want those forums that you can post in
			$sql = 'SELECT forum_posts_per_page, forum_id
					FROM ' . FORUMS_TABLE . '
					WHERE forum_type = ' . FORUM_POST . '
					ORDER BY forum_id DESC';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$posts_per_page[$row['forum_id']] = array(
					'forum_id'			=> $row['forum_id'],
					'forum_posts_per_page'	=> $row['forum_posts_per_page'],
				);
			}
			$this->db->sql_freeresult($result);

			// cache this data for ever, can only change in ACP
			$this->cache->put('_forum_posts_per_page', $posts_per_page);
		}

		$fpp = 0;
		if (sizeof($posts_per_page))
		{
			foreach ($posts_per_page as $row)
			{
				if ($row['forum_id'] == $forum_id)
				{
					$fpp = $row['forum_posts_per_page'];
				}
			}
		}
		return $fpp;
	}
}
