<?php
/**
*
* @package Forum Posts Per Page
* @copyright (c) 2014 RMcGirr83
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace rmcgirr83\forumpostsperpage\migrations;

class install_forumpostsperpage extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['forum_posts_per_page_version']) && version_compare($this->config['forum_posts_per_page_version'], '1.0.0', '>=');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'forums'	=> array(
					'forum_posts_per_page'	=> array('USINT', 0),
				),
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('forum_posts_per_page_version', '1.0.0')),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'forums'	=>array(
					'forum_posts_per_page',
				),
			),
		);
	}
	
	public function revert_data()
	{
		return array(
			array('config.remove', array('forum_posts_per_page_version')),
		);
	}
}
