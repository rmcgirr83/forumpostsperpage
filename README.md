phpbb-3.1-ext-forumpostsperpage
=========================

phpBB 3.1 extension that alters the number of posts to show in a topic per forum.

This extension is the 3.1.x version of the [3.0.x Forum Based Posts Per Page](https://www.phpbb.com/customise/db/mod/forum_based_posts_per_page/)

[![Build Status](https://travis-ci.org/RMcGirr83/forumpostsperpage.svg)](https://travis-ci.org/RMcGirr83/forumpostsperpage)
## Installation

### 1. clone
Clone (or download and move) the repository into the folder phpBB3/ext/rmcgirr83/forumpostsperpage:

```
cd phpBB3
git clone https://github.com/RMcGirr83/forumpostsperpage.git ext/rmcgirr83/forumpostsperpage/
```

### 2. activate
Go to admin panel -> tab customise -> Manage extensions -> enable Forum Posts Per Page

### 3. edit viewtopic.php file
Unfortunately there is not a very clean way currently in 3.1.x version of phpBB to overwrite the config['posts_per_page'] setting in viewtopic so the following edit must be done to the file

OPEN

viewtopic.php

FIND

// What is start equal to?

ADD BEFORE

`// extension forum posts per page
if (!empty($topic_data['forum_posts_per_page']))
{
	// overwrite config['posts_per_page']
	$config['posts_per_page'] = $topic_data['forum_posts_per_page'];
}
// end extension mod to file`

Save and upload file and ensure you use a strict text editor such as notepad++

### 4.
Visit each forum and in the general settings you can set how many posts to display for each topic associated with that forum.
