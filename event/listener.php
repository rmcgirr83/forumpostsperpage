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

	/** @var \phpbb\request\request */
	protected $request;

	public function __construct(\phpbb\request\request $request)
	{
		$this->request = $request;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_manage_forums_request_data'		=> 'acp_manage_forums_request_data',
			'core.acp_manage_forums_initialise_data'	=> 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form'		=> 'acp_manage_forums_display_form',
			'core.acp_manage_forums_validate_data'		=> 'acp_manage_forums_validate_data',
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
}
