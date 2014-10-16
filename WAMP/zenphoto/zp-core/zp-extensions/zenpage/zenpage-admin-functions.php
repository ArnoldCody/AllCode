<?php
/**
 * zenpage admin functions
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins
 * @subpackage zenpage
 */

/**
 * Calls the Zenpage class
 *
 */
require_once("zenpage-class-page.php");
require_once("zenpage-class-news.php");
require_once("zenpage-functions.php");

global $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_current_category;

/**
 * Retrieves posted expiry date and checks it against the current date/time
 * Returns the posted date if it is in the future
 * Returns NULL if the date is past
 *
 * @return string
 */
function getExpiryDatePost() {
	$expiredate = sanitize($_POST['expiredate']);
	if ($expiredate > date(date('Y-m-d H:i:s'))) return $expiredate;
	return NULL;
}

/**
 * processes the taglist save
 *
 * @param object $object the object on which the save happened
 */
function processTags($object) {
	$tagsprefix = 'tags_';
	$tags = array();
	$l = strlen($tagsprefix);
	foreach ($_POST as $key => $value) {
		$key = postIndexDecode($key);
		if (substr($key, 0, $l) == $tagsprefix) {
			if ($value) {
				$tags[] = substr($key, $l);
			}
		}
	}
	$tags = array_unique($tags);
	$object->setTags(sanitize($tags, 3));
}

/**************************
/* page functions
***************************/

/**
 * processes password saves
 * returns error indicating mismatch state
 * @param object $page
 * @return string
 */
function processPasswordSave($page) {
	$notify = $fail = '';
	if (sanitize($_POST['password_enabled'])) {
		$olduser = $page->getUser();
		$newuser = $_POST['page_user'];
		$pwd = trim($_POST['pagepass']);
		if (($olduser != $newuser)) {
			if (!empty($newuser) && empty($pwd) && empty($pwd2)) {
				$fail = 'user';
			}
		}
		if (!$fail && $_POST['pagepass'] == $_POST['pagepass_2']) {
			$page->setUser($newuser);
			$page->setPasswordHint(process_language_string_save('page_hint', 3));
			if (empty($pwd)) {
				if (empty($_POST['pagepass'])) {
					$page->setPassword(NULL);  // clear the password
				}
			} else {
				$page->setPassword($pwd);
			}
		} else {
			if (empty($fail)) {
				$notify = 'pass';
			} else {
				$notify = $fail;
			}
		}
	}
	return $notify;
}

/**
 * Updates a new page to that database and returns the object of that page
 *
 * @return object
 */
function addPage(&$reports) {
	$date = date('Y-m-d_H-i-s');
	$title = process_language_string_save("title",2);
	$titlelink = seoFriendly(get_language_string($title));
	if (empty($titlelink)) $titlelink = seoFriendly($date);

	$author = sanitize($_POST['author']);
	$content = process_language_string_save("content",0); // TinyMCE already clears unallowed code
	$extracontent = process_language_string_save("extracontent",0); // TinyMCE already clears unallowed code
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$expiredate = getExpiryDatePost();
	$commentson = getcheckboxState('commentson');
	$permalink = getcheckboxState('permalink');
	$custom = process_language_string_save("custom_data",1);
	$codeblock1 = sanitize($_POST['codeblock1'], 0);
	$codeblock2 = sanitize($_POST['codeblock2'], 0);
	$codeblock3 = sanitize($_POST['codeblock3'], 0);
	$codeblock = serialize(array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3));
	$locked = getcheckboxState('locked');

	$sql = 'SELECT `id` FROM '.prefix('pages').' WHERE `titlelink`='.db_quote($titlelink);
	$rslt = query_single_row($sql,false);
	if ($rslt) {
		$titlelink .= '_'.seoFriendly($date); // force unique so that data may be saved.
	}
	$page = new ZenpagePage($titlelink);
	$notice = processPasswordSave($page);
	$page->setTitle($title);
	$page->setContent($content);
	$page->setExtracontent($extracontent);
	$page->setCustomData(zp_apply_filter('save_page_custom_data', $custom,$page));
	$page->setShow($show);
	$page->setDateTime($date);
	$page->setCommentsAllowed($commentson);
	$page->setCodeblock($codeblock);
	$page->setAuthor($author);
	$page->setPermalink($permalink);
	$page->setLocked($locked);
	$page->setExpiredate($expiredate);
	processTags($page);
	$msg = zp_apply_filter('new_page', '', $page);
	$page->save();
	if(empty($title)) {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("Page <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink)).'</p>';
	} else if ($notice == 'user') {
		$reports[] =  "<p class='errorbox fade-message'>".gettext('You must supply a password for the Protected Page user').'</p>';
	} else if ($notice == 'pass') {
		$reports[] =  "<p class='errorbox fade-message'>".gettext('Your passwords were empty or did not match').'</p>';
	} else {
		$reports[] =  "<p class='messagebox fade-message'>".sprintf(gettext("Page <em>%s</em> added"),$titlelink).'</p>';
	}
	if ($msg) {
		$reports[] =  $msg;
	}
	return $page;
}



/**
 * Updates a page and returns the object of that page
 *
 * @return object
 */
function updatePage(&$reports) {
	$title = process_language_string_save("title",2);
	$author = sanitize($_POST['author']);
	$content = process_language_string_save("content",0); // TinyMCE already clears unallowed code
	$extracontent = process_language_string_save("extracontent",0); // TinyMCE already clears unallowed code
	$custom = process_language_string_save("custom_data",1);
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$lastchange = sanitize($_POST['lastchange']);
	$lastchangeauthor = sanitize($_POST['lastchangeauthor']);
	$expiredate = getExpiryDatePost();
	$commentson = getcheckboxState('commentson');
	$permalink = getcheckboxState('permalink');
	$codeblock1 = sanitize($_POST['codeblock1'], 0);
	$codeblock2 = sanitize($_POST['codeblock2'], 0);
	$codeblock3 = sanitize($_POST['codeblock3'], 0);
	$codeblock = serialize(array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3));
	$locked = getcheckboxState('locked');
	$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old']);

	if (getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'],3);
		if (empty($titlelink)) {
			$titlelink = seoFriendly(get_language_string($title));
			if (empty($titlelink)) {
				$titlelink = seoFriendly($date);
			}
		}
	} else {
		if (!$permalink) {	//	allow the link to change
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}
	$id = sanitize($_POST['id']);
	$rslt = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$rslt = query('UPDATE '.prefix('pages').' SET `titlelink`='.db_quote($titlelink).' WHERE `id`='.$id,false);
		if (!$rslt) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		}
	}
	// update page
	$page = new ZenpagePage($titlelink);
	$notice = processPasswordSave($page);
	$page->setTitle($title);
	$page->setContent($content);
	$page->setExtracontent($extracontent);
	$page->setCustomData(zp_apply_filter('save_page_custom_data',$custom,$page));
	$page->setShow($show);
	$page->setDateTime($date);
	$page->setCommentsAllowed($commentson);
	$page->setCodeblock($codeblock);
	$page->setAuthor($author);
	$page->setLastchange($lastchange);
	$page->setLastchangeauthor($lastchangeauthor);
	$page->setPermalink($permalink);
	$page->setLocked($locked);
	$page->setExpiredate($expiredate);
	if (getcheckboxState('resethitcounter')) {
		$page->set('hitcounter',0);
	}
	processTags($page);
	$msg = zp_apply_filter('update_page', '', $page, $oldtitlelink);
	$page->save();

	if (!$rslt) {
		$reports[] = "<p class='errorbox fade-message'>".sprintf(gettext("A page with the title/titlelink <em>%s</em> already exists!"),$titlelink).'</p>';
	} else 	if(empty($title)) {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("Page <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink)).'</p>';
	} else if ($notice == 'user') {
		$reports[] =  "<p class='errorbox fade-message'>".gettext('You must supply a password for the Protected Page user').'</p>';
	} else if ($notice == 'pass') {
		echo "<p class='errorbox fade-message'>".gettext('Your passwords were empty or did not match').'</p>';
	} else {
		$reports[] =  "<p class='messagebox fade-message'>".sprintf(gettext("Page <em>%s</em> updated"),$titlelink).'</p>';
	}
	if ($msg) {
		$reports[] =  $msg;
	}
	return $page;
}

/**
 * Deletes a page (and also if existing its subpages) from the database
 *
 */
function deletePage($titlelink) {
	if (is_object($titlelink)) {
		$obj = $titlelink;
	} else {
		$obj = new ZenpagePage(sanitize($titlelink));
	}
	$result = $obj->remove();
	if($result) {
		if (is_object($titlelink)) {
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER. '/zenpage/admin-pages.php?deleted');
			exit();
		}
		return "<p class='messagebox fade-message'>".gettext("Page successfully deleted!")."</p>";
	}
	return "<p class='errorbox fade-message'>".gettext("Page delete failed!")."</p>";
}




/**
 * Prints the table part of a single page item for the sortable pages list
 *
 * @param object $page The array containing the single page
 * @param bool $flag set to true to flag the element as having a problem with nesting level
 */
function printPagesListTable($page, $flag) {
	if ($flag) {
		$img = '../../images/drag_handle_flag.png';
	} else {
		$img = '../../images/drag_handle.png';
	}
	?>
 <div class='page-list_row'>
	 <div class="page-list_title">
		<?php if(checkIfLockedPage($page)) {
			echo "<a href='admin-edit.php?page&amp;titlelink=".urlencode($page->getTitlelink())."'> "; checkForEmptyTitle($page->getTitle(),"page"); echo "</a>".checkHitcounterDisplay($page->getHitcounter());
		} else {
			echo $page->getTitle(); checkHitcounterDisplay($page->getShow());
		}	?>
		</div>
		<div class="page-list_extra">
			<?php
			 checkIfScheduled($page);
			 checkIfExpires($page);
			?>
		</div>
		<div class="page-list_extra">
			<?php echo html_encode($page->getAuthor()) ;?>
		</div>
		<div class="page-list_iconwrapper">
	<div class="page-list_icon">
	<?php
	if ($page->isProtected() && (getOption('gallery_security') != 'private')) {
		echo '<img src="../../images/lock.png" style="border: 0px;" alt="'.gettext('Password protected').'" title="'.gettext('Password protected').'" />';
	}
	?>
	</div>

	<?php if(checkIfLockedPage($page)) { ?>
	<div class="page-list_icon">
		<?php printPublishIconLink($page,"page"); ?>
	</div>
	<div class="page-list_icon">
		<?php
		if ($page->getCommentsAllowed()) {
			?>
			<a href="?commentson=1&amp;id=<?php echo $page->getID(); ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>" title="<?php echo gettext('Disable comments'); ?>">
				<img src="../../images/comments-on.png" alt="<?php echo gettext("Comments on"); ?>" style="border: 0px;"/>
			</a>
			<?php
		} else {
			?>
			<a href="?commentson=0&amp;id=<?php echo $page->getID(); ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>" title="<?php echo gettext('Enable comments'); ?>">
				<img src="../../images/comments-off.png" alt="<?php echo gettext("Comments off"); ?>" style="border: 0px;"/>
			</a>
			<?php
		}
		?>
	</div>
	<?php } else { ?>
	<div class="page-list_icon">
		<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</div>
	<div class="page-list_icon">
		<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</div>
	<?php } ?>

		<div class="page-list_icon">
			<a href="../../../index.php?p=pages&amp;title=<?php echo js_encode($page->getTitlelink()) ;?>" title="<?php echo gettext("View page"); ?>">
			<img src="images/view.png" alt="view" />
			</a>
		</div>

	<?php if(checkIfLockedPage($page)) { ?>
	<div class="page-list_icon">
		<a href="?hitcounter=1&amp;id=<?php echo $page->getID(); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('hitcounter')?>" title="<?php echo gettext("Reset hitcounter"); ?>">
		<img src="../../images/reset.png" alt="<?php echo gettext("Reset hitcounter"); ?>" /></a>
	</div>
	<div class="page-list_icon">
		<a href="javascript:confirmDelete('admin-pages.php?delete=<?php echo $page->getTitlelink(); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('delete')?>',deletePage)" title="<?php echo gettext("Delete page"); ?>">
		<img src="../../images/fail.png" alt="delete" /></a>
	</div>
	<div class="page-list_icon">
		<input class="checkbox" type="checkbox" name="ids[]" value="<?php echo $page->getID(); ?>" onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
	</div>
	<?php } else { ?>
	<div class="page-list_icon">
		<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</div>
	<div class="page-list_icon">
		<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</div>
	<div class="page-list_icon">
		<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</div>
	<?php } ?>
	</div><!--  icon wrapper end -->
 </div>
<?php
}

/**************************
/* news article functions
***************************/

/**
 * Adds a new news article to the database from $_POST data and returns the object of that article
 *
 * @return object
 */
function addArticle(&$reports) {
	$date = date('Y-m-d_H-i-s');
	$title = process_language_string_save("title",2);
	$titlelink = seoFriendly(get_language_string($title));
	if (empty($titlelink)) $titlelink = seoFriendly($date);

	$author = sanitize($_POST['author']);
	$content = process_language_string_save("content",0); // TinyMCE already clears unallowed code
	$extracontent = process_language_string_save("extracontent",0); // TinyMCE already clears unallowed code
	$custom = process_language_string_save("custom_data",1);
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$expiredate = getExpiryDatePost();
	$permalink = getcheckboxState('permalink');
	$commentson = getcheckboxState('commentson');
	$codeblock1 = sanitize($_POST['codeblock1'], 0);
	$codeblock2 = sanitize($_POST['codeblock2'], 0);
	$codeblock3 = sanitize($_POST['codeblock3'], 0);
	$codeblock = serialize(array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3));
	$locked = getcheckboxState('locked');

	$rslt = query_single_row('SELECT `id` FROM '.prefix('news').' WHERE `titlelink`='.db_quote($titlelink),false);
	if ($rslt) {
		$titlelink .= '_'.seoFriendly($date); // force unique so that data may be saved.
	}
	// create new article
	$article = new ZenpageNews($titlelink);
	$article->setTitle($title);
	$article->setContent($content);
	$article->setExtracontent($extracontent);
	$article->setCustomData(zp_apply_filter('save_article_custom_data',$custom,$article));
	$article->setShow($show);
	$article->setDateTime($date);
	$article->setCommentsAllowed($commentson);
	$article->setCodeblock($codeblock);
	$article->setAuthor($author);
	$article->setPermalink($permalink);
	$article->setLocked($locked);
	$article->setExpiredate($expiredate);
	$article->setSticky(sanitize_numeric($_POST['sticky']));
	processTags($article);
	$msg = zp_apply_filter('new_article', '', $article);
	$article->save();
	// create news2cat rows
	$result2 = query_full_array("SELECT * FROM ".prefix('news_categories')." ORDER BY titlelink");
	foreach ($result2 as $cat) {
		if (isset($_POST["cat".$cat['id']])) {
			query("INSERT INTO ".prefix('news2cat')." (cat_id, news_id) VALUES ('".$cat['id']."', '".$article->get('id')."')");
		}
	}
	if(empty($title)) {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("Article <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink)).'</p>';
	} else {
		$reports[] =  "<p class='messagebox fade-message'>".sprintf(gettext("Article <em>%s</em> added"),$titlelink).'</p>';
	}
	if ($msg) {
		$reports[] =  $msg;
	}
	return $article;
}


/**
 * Updates a news article and returns the object of that article
 *
 * @return object
 */
function updateArticle(&$reports) {
	$date = date('Y-m-d_H-i-s');
	$title = process_language_string_save("title",2);
	$author = sanitize($_POST['author']);
	$content = process_language_string_save("content",0); // TinyMCE already clears unallowed code
	$extracontent = process_language_string_save("extracontent",0); // TinyMCE already clears unallowed code
	$custom = process_language_string_save("custom_data",1);
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$expiredate = getExpiryDatePost();
	$permalink = getcheckboxState('permalink');
	$lastchange = sanitize($_POST['lastchange']);
	$lastchangeauthor = sanitize($_POST['lastchangeauthor']);
	$commentson = getcheckboxState('commentson');
	$codeblock1 = sanitize($_POST['codeblock1'], 0);
	$codeblock2 = sanitize($_POST['codeblock2'], 0);
	$codeblock3 = sanitize($_POST['codeblock3'], 0);
	$codeblock = serialize(array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3));
	$locked = getcheckboxState('locked');
	$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old'],3);

	if (getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'],3);
		if (empty($titlelink)) {
			$titlelink = seoFriendly(get_language_string($title));
			if (empty($titlelink)) {
				$titlelink = seoFriendly($date);
			}
		}
	} else {
		if (!$permalink) {	//	allow the title link to change.
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}

	$id = sanitize($_POST['id']);
	$rslt = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$rslt = query('UPDATE '.prefix('news').' SET `titlelink`='.db_quote($titlelink).' WHERE `id`='.$id,false);
		if (!$rslt) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		}
	}
	// update article
	$article = new ZenpageNews($titlelink);
	$article->setTitle($title);
	$article->setContent($content);
	$article->setExtracontent($extracontent);
	$article->setCustomData(zp_apply_filter('save_article_custom_data', $custom,$article));
	$article->setShow($show);
	$article->setDateTime($date);
	$article->setCommentsAllowed($commentson);
	$article->setCodeblock($codeblock);
	$article->setAuthor($author);
	$article->setLastchange($lastchange);
	$article->setLastchangeauthor($lastchangeauthor);
	$article->setPermalink($permalink);
	$article->setLocked($locked);
	$article->setExpiredate($expiredate);
	$article->setSticky(sanitize_numeric($_POST['sticky']));
	if(getcheckboxState('resethitcounter')) {
		$article->set('hitcounter',0);
	}
	processTags($article);
	$msg = zp_apply_filter('update_article', '', $article, $oldtitlelink);
	$article->save();
	// create news2cat rows
	$result2 = query_full_array("SELECT * FROM ".prefix('news_categories')." ORDER BY id");
	foreach($result2 as $cat) {

		// if category is sent
		if(isset($_POST["cat".$cat['id']])) {
			// check if category is already set in db, if not add it to news2cat
			$checkcat = query_single_row("SELECT cat_id, news_id FROM ".prefix('news2cat')." WHERE cat_id = ".$cat['id']. " AND news_id = ".$article->get('id'));
			if(!$checkcat) {
				query("INSERT INTO ".prefix('news2cat')." (cat_id, news_id) VALUES ('".$cat['id']."', '".$article->get('id')."')");
			}
			// if category is not sent, delete it from news2cat
		} else {
			query("DELETE FROM ".prefix('news2cat')." WHERE cat_id = ".$cat['id']." AND news_id = ".$article->get('id'));
		}
	}
	if (!$rslt) {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("An article with the title/titlelink <em>%s</em> already exists!"),$titlelink).'</p>';
	} else if(empty($title)) {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("Article <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink)).'</p>';
	} else {
		$reports[] =  "<p class='messagebox fade-message'>".sprintf(gettext("Article <em>%s</em> updated"),$titlelink).'</p>';
	}
	if ($msg) {
		$reports[] =  $msg;
	}
	return $article;
}


/**
 * Deletes an news article from the database
 *
 */
function deleteArticle($titlelink) {
	if (is_object($titlelink)) {
		$obj = $titlelink;
	} else {
		$obj = new ZenpageNews(sanitize($titlelink));
	}
	$result = $obj->remove();
	if($result) {
		if (is_object($titlelink)) {
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER. '/zenpage/admin-news-articles.php?deleted');
			exit();
		}
		return "<p class='messagebox fade-message'>".gettext("Article successfully deleted!")."</p>";
	}
	return "<p class='errorbox fade-message'>".gettext("Article delete failed!")."</p>";
}


/**
 * Print the categories of a news article for the news articles list
 *
 * @param obj $obj object of the news article
 */
function printArticleCategories($obj) {
	 $cat = $obj->getCategories();
	$number = 0;
	foreach ($cat as $cats) {
		$number++;
		if($number != 1) {
			echo ", ";
		}
		echo get_language_string($cats['titlelink']);
	}
}

/**
 * Print the categories of a news article for the news articles list
 *
 * @param obj $obj object of the news article
 */
function printPageArticleTags($obj) {
	 $tags = $obj->getTags();
	$number = 0;
	foreach ($tags as $tag) {
		$number++;
		if($number != 1) {
			echo ", ";
		}
		echo get_language_string($tag);
	}
}


/**
 * Prints the checkboxes to select and/or show the category of an news article on the edit or add page
 *
 * @param int $id ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 */
function printCategorySelection($id='', $option='') {
	$selected = '';
	echo "<ul class='zenpagechecklist'>\n";
	foreach ($all_cats as $cats) {
		$catobj = new ZenpageCategory($cats['titlelink']);
		if($option != "all") {
			$cat2news = query_single_row("SELECT cat_id FROM ".prefix('news2cat')." WHERE news_id = ".$id." AND cat_id = ".$catobj->getID());
			if($cat2news['cat_id'] != "") {
				$selected ="checked ='checked'";
			}
		}
		$catname = $catobj->getTitle();
		$catlink = $catobj->getTitlelink();
		if($catobj->isProtected() && ((getOption('gallery_security') != 'private'))) {
			$protected = '<img src="'.WEBPATH.'/'.ZENFOLDER.'/images/lock.png" alt="'.gettext('password protected').'" />';
		} else {
			$protected = '';
		}
		$catid = $catobj->getID();
		echo "<li class=\"hasimage\" ><label for='cat".$catid."'><input name='cat".$catid."' id='cat".$catid."' type='checkbox' value='".$catid."' ".$selected." />".$catname." ".$protected."</label></li>\n";
	}
	echo "</ul>\n";
}


/**
 * Prints the dropdown menu for the date archive selector for the news articles list
 *
 */
function printArticleDatesDropdown() {
	$datecount = getAllArticleDates();
	$currentpage = getCurrentAdminNewsPage();
	$lastyear = "";
	$nr = "";
 ?>
	<form name="AutoListBox1" style="float:left; margin-left: 10px;" action="#" >
	<select name="ListBoxURL" size="1" onchange="gotoLink(this.form)">
 <?php
		if(!isset($_GET['date'])) {
			$selected = 'selected="selected"';
		 } else {
				$selected = "";
			}
		 echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,false,true)."'>".gettext("View all months")."</option>";
		while (list($key, $val) = each($datecount)) {
		$nr++;
		if ($key == '0000-00-01') {
			$year = "no date";
			$month = "";
		} else {
			$dt = strftime('%Y-%B', strtotime($key));
			$year = substr($dt, 0, 4);
			$month = substr($dt, 5);
		}
		if(isset($_GET['category'])) {
				$catlink = "&amp;category=".$_GET['category'];
			} else {
				$catlink = "";
			}
		$check = $month."-".$year;
		 if(isset($_GET['date']) AND $_GET['date'] == substr($key,0,7)) {
				$selected = "selected='selected'";
		 } else {
				$selected = "";
			}
			if(isset($_GET['date'])) {
				echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,false,true)."'>$month $year ($val)</option>\n";
			} else {
				echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage."&amp;date=".substr($key,0,7).getNewsAdminOptionPath(true,false,true)."'>$month $year ($val)</option>\n";
			}
	}
?>
	</select>
	<script language="JavaScript" type="text/javascript" >
		// <!-- <![CDATA[
		function gotoLink(form) {
		var OptionIndex=form.ListBoxURL.selectedIndex;
		parent.location = form.ListBoxURL.options[OptionIndex].value;}
		// ]]> -->
	</script>
	</form>
<?php
}

/**
 * Prints news articles list page navigation
 *
 */
function printArticlesPageNav() {
	global $_zp_zenpage_total_pages;
	$current = getCurrentAdminNewsPage();
	$total = $_zp_zenpage_total_pages;
	$navlen = 9;
	if($total > 1) {
		$extralinks = 4;
		$len = floor(($navlen-$extralinks) / 2);
		$j = max(round($extralinks/2), min($current-$len-(2-round($extralinks/2)), $total-$navlen+$extralinks-1));
		$ilim = min($total, max($navlen-round($extralinks/2), $current+floor($len)));
		$k1 = round(($j-2)/2)+1;
		$k2 = $total-round(($total-$ilim)/2);
		echo "<ul class=\"pagelist\">";
		echo "<li class=\"prev\">";
		if ($current != 1) {
			echo "<a href='admin-news-articles.php?pagenr=".($current - 1).getNewsAdminOptionPath(true,true,true)."' title='".gettext("Prev Page")." ".($current - 1)."' >&laquo; ".gettext("prev")."</a>\n";
		} else {
			echo "<span class='disabledlink'>&laquo; ".gettext("prev")."</span>\n";
		}
		echo "</li>\n";

		echo '<li class="'.($current==1?'current':'first').'">';
		echo "<a href='admin-news-articles.php?pagenr=1".getNewsAdminOptionPath(true,true,true)."' title='".gettext("First Page")."' >1</a>\n";
		echo "</li>\n";
		if ($j>2) {
			echo "<li>";
			$linktext = ($j-1>2)?'...':$k1;
			echo "<a href='admin-news-articles.php?pagenr=".$k1.getNewsAdminOptionPath(true,true,true)."' title='page ".sprintf(ngettext('Page %u','Page %u',$k1),$k1)."'>".$linktext."</a>";
			//echo "<a href=\"".getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().$k1."\" title=\"".sprintf(ngettext('Page %u','Page %u',$k1),$k1)."\">".($j-1>2)?'...':$k1."</a>";
			echo "</li>\n";
		}

		for ($i=$j; $i <= $ilim; $i++) {
			echo "<li" . (($i == $current) ? " class=\"current\"" : "") . ">";
			if($i == $current) {
				echo $i;
			} else {
				echo "<a href='admin-news-articles.php?pagenr=".$i.getNewsAdminOptionPath(true,true,true)."' title='".sprintf(ngettext('Page %1$u','Page %1$u', $i),$i)."'>".$i."</a>\n";
			}
			echo "</li>\n";
		}
		if ($i < $total) {
			echo "<li>";
			$linktext = ($total-$i>1)?'...':$k2;
			echo "<a href='admin-news-articles.php?pagenr=".$k2.getNewsAdminOptionPath(true,true,true)."' title='".sprintf(ngettext('Page %u','Page %u',$k2),$k2)."'>".$linktext."</a>";
			echo "</li>\n";
		}
		if ($i <= $total) {
			echo "\n  <li class=\"last\">";
			echo "<a href='admin-news-articles.php?pagenr=".$total.getNewsAdminOptionPath(true,true,true)."' title='".sprintf(ngettext('Page %u','Page %u',$total),$total)."'>".$total."</a>";
			echo "</li>";
		}

		if ($current != $total)	{
			echo "<li class='next'><a href='admin-news-articles.php?pagenr=".($current+1).getNewsAdminOptionPath(true,true,true)."' title='".gettext("Next page")." ".($current+1)."'>".gettext("next")." &raquo;</a></li>\n";
		} else {
			echo "<li class='next'><span class='disabledlink'>".gettext("next")." &raquo;</span></li>\n";
		}
		echo "</li>\n";
	}
}

/**
 * Creates the admin paths for news articles if you use the dropdowns for category, published and date together
 *
 * @param bool $categorycheck true or false if 'category' should be included in the url
 * @param bool $postedcheck true or false if 'date' should be included in the url
 * @param bool $publishedcheck true or false if 'published' should be included in the url
 * @return string
 */
function getNewsAdminOptionPath($categorycheck='', $postedcheck='',$publishedcheck='') {
	$category = "";
	$posted = "";
	$published = "";
	if(isset($_GET['category']) AND $categorycheck === true) {
		$category = "&amp;category=".$_GET['category'];
	}
	if(isset($_GET['date']) AND $postedcheck === true) {
		$posted = "&amp;date=".$_GET['date'];
	}
	if(isset($_GET['published']) AND $publishedcheck === true) {
		$published = "&amp;published=".$_GET['published'];
	}
	$optionpath = $category.$posted.$published;
	return $optionpath;
}


/**
 * Prints the dropdown menu for the published/un-publishd selector for the news articles list
 *
 */
function printUnpublishedDropdown() {
	$currentpage = getCurrentAdminNewsPage();
?>
<form name="AutoListBox3" style="float: left; margin-left: 10px;"	action="#">
	<select name="ListBoxURL" size="1"	onchange="gotoLink(this.form)">
	<?php
	$all="";
	$published="";
	$unpublished="";
	$sticky = '';
	if(isset($_GET['published'])) {
		switch ($_GET['published']) {
			case "no":
				$unpublished="selected='selected'";
				break;
			case "yes":
				$published="selected='selected'";
				break;
			case 'sticky':
				$sticky="selected='selected'";
				break;
		}
	} else {
		$all="selected='selected'";
	}
	echo "<option $all value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."'>".gettext("All articles")."</option>\n";
	echo "<option $published value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."&amp;published=yes'>".gettext("Published")."</option>\n";
	echo "<option $unpublished value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."&amp;published=no'>".gettext("Un-published")."</option>\n";
	echo "<option $sticky value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."&amp;published=sticky'>".gettext("Sticky")."</option>\n";
	?>
</select>
	<script language="JavaScript" type="text/javascript">
		// <!-- <![CDATA[
		function gotoLink(form) {
		var OptionIndex=form.ListBoxURL.selectedIndex;
		parent.location = form.ListBoxURL.options[OptionIndex].value;}
		// ]]> -->
	</script>
</form>
<?php
}


/**************************
/* Category functions
***************************/

/**
 * Handles saving of News Category passwords
 */
function processCategoryPasswordSave($cat) {
	global $_zp_authority;
	$notify = $fail = '';
	if (sanitize($_POST['password_enabled'])) {
		$olduser = $_POST['olduser'];
		$newuser = $_POST['category_user'];
		$pwd = trim($_POST['categorypass']);
		if (($olduser != $newuser)) {
			if (!empty($newuser) && empty($pwd) && empty($pwd2)) {
				$fail = 'user';
			}
		}
		if (!$fail && $_POST['categorypass'] == $_POST['categorypass_2']) {
			$cat->setUser($newuser);
			$cat->setPasswordHint(process_language_string_save('category_hint', 3));
			if (empty($pwd)) {
				if (empty($_POST['categorypass'])) {
					$cat->setPassword(NULL);  // clear the password
				}
			} else {
				$cat->setPassword($pwd);
			}
		} else {
			if (empty($fail)) {
				$notify = 'pass';
			} else {
				$notify = $fail;
			}
		}
	}
	return $notify;
}

/**
 * Adds a category to the database
 *
 */
function addCategory(&$reports) {
	$date = date('Y-m-d_H-i-s');
	$title = process_language_string_save("title",2); // so that no \ are shown in the 'Category x added' message
	$titlelink = seoFriendly(get_language_string($title));
	$desc = process_language_string_save("desc",2);
	$custom = process_language_string_save("custom_data",2);
	if (empty($titlelink)) $titlelink = seoFriendly($date);

	$sql = 'SELECT `id` FROM '.prefix('news_categories').' WHERE `titlelink`='.db_quote($titlelink);
	$rslt = query_single_row($sql,false);
	if ($rslt) {
		$titlelink .= '_'.seoFriendly($date); // force unique so that data may be saved.
	}
	// create new category
	$cat = new ZenpageCategory($titlelink);
	$notice = processCategoryPasswordSave($cat);
	$cat->setPermalink(getcheckboxState('permalink'));
	$cat->set('title',$title);
	$cat->setDesc($desc);
	$cat->setCustomData(zp_apply_filter('save_category_custom_data', $custom,$cat));
	$msg = zp_apply_filter('new_category','', $cat);
	$cat->save();
	if(empty($title)) {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("Category <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"),$titlelink).'</p>';
	} else if ($notice == 'user') {
		$reports[] =  "<p class='errorbox fade-message'>".gettext('You must supply a password for the Protected Category user').'</p>';
	} else if ($notice == 'pass') {
		$reports[] =  "<p class='errorbox fade-message'>".gettext('Your passwords were empty or did not match').'</p>';
	} else {
		$reports[] =  "<p class='messagebox fade-message'>".sprintf(gettext("Category <em>%s</em> added"),$titlelink).'</p>';
	}
	if ($msg) {
		$reports[] =  $msg;
	}
}


/**
 * Updates a category
 *
 */
function updateCategory(&$reports) {
	$date = date('Y-m-d_H-i-s');
	$id = sanitize_numeric($_POST['id']);
	$permalink = getcheckboxState('permalink');
	$title = process_language_string_save("title",2);
	$desc = process_language_string_save("desc",2);
	$custom = process_language_string_save("custom_data",2);
	$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old'],3);
	if (getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'],3);
		if (empty($titlelink)) {
			$titlelink = seoFriendly(get_language_string($title));
			if(empty($titlelink)) {
				$titlelink = seoFriendly($date);
			}
		}
	} else {
		if (!$permalink) {	//	allow the link to change
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}
	$titleok = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$titleok = query('UPDATE '.prefix('news_categories').' SET `titlelink`='.db_quote($titlelink).' WHERE `id`='.$id,false);
		if (!$titleok) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		}
	} else {
		$titlelink = $oldtitlelink;
	}
	//update category
	$cat = new ZenpageCategory($titlelink);
	$notice = processCategoryPasswordSave($cat);
	$cat->setPermalink(getcheckboxState('permalink'));
	$cat->set('title',$title);
	$cat->setDesc($desc);
	$cat->setCustomData(zp_apply_filter('save_category_custom_data', $custom,$cat));
	if (getcheckboxState('resethitcounter')) {
		$cat->set('hitcounter',0);
	}
	$msg = zp_apply_filter('update_category', '', $cat, $oldtitlelink);
	$cat->save();
	if($titleok) {
		if(empty($titlelink) OR empty($title)) {
			$reports[] =  "<p class='errorbox fade-message'>".gettext("You forgot to give your category a <strong>title or titlelink</strong>!")."</p>";
		} else if ($notice == 'user') {
			$reports[] =  "<p class='errorbox fade-message'>".gettext('You must supply a password for the Protected Category user').'</p>';
		} else if ($notice == 'pass') {
			$reports[] =  "<p class='errorbox fade-message'>".gettext('Your passwords were empty or did not match').'</p>';
		} else {
			$reports[] =  "<p class='messagebox fade-message'>".gettext("Category updated!")."</p>";
		}
	} else {
		$reports[] =  "<p class='errorbox fade-message'>".sprintf(gettext("A category with the title/titlelink <em>%s</em> already exists!"),html_encode($cat->getTitle()))."</p>";
	}
	if ($msg) {
		$reports[] =  $msg;
	}
	return $cat;
}


/**
 * Deletes a category (and also if existing its subpages) from the database
 *
 */
function deleteCategory($titlelink) {
	$obj = new ZenpageCategory(sanitize($titlelink));
	$result = $obj->remove();
	if($result) {
		return  "<p class='messagebox fade-message'>".gettext("Category successfully deleted!")."</p>";
	}
	return "<p class='errorbox fade-message'>".gettext("Category  delete failed!")."</p>";
}


/**
 * Prints the list entry of a single category for the sortable list
 *
 * @param array $cat Array storing the db info of the category
 * @param string $flag If the category is protected
 * @return string
 */
function printCategoryListSortableTable($cat,$flag) {
	if ($flag) {
		$img = '../../images/drag_handle_flag.png';
	} else {
		$img = '../../images/drag_handle.png';
	}
	$count = countArticles($cat->getTitlelink(),false);
	if($cat->getTitle()) {
		$cattitle = $cat->getTitle();
	} else {
		$cattitle = "<span style='color:red; font-weight: bold'>".gettext("Untitled category")."</span>";
	}
	?>
	 <div class='page-list_row'>
		<div class='page-list_title' >
		<?php echo "<a href='admin-edit.php?category&amp;titlelink=".$cat->getTitlelink()."' title='".gettext('Edit this category')."'>".$cattitle."</a>".checkHitcounterDisplay($cat->getHitcounter()); ?>
		</div>
		<div class="page-list_extra"><?php echo $count; ?> <?php echo gettext("articles"); ?>
		</div>
		<div class="page-list_iconwrapper">
			<div class="page-list_icon"><?php
			$password = $cat->getPassword();
			if (!empty($password)  && (getOption('gallery_security') != 'private')) {
				echo '<img src="../../images/lock.png" style="border: 0px;" alt="'.gettext('Password protected').'" title="'.gettext('Password protected').'" />';
			}
			?>
			</div>
			<div class="page-list_icon">
			<?php if($count == 0) { ?>
				<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
			<?php
			} else {
			?>
				<a href="../../../index.php?p=news&amp;category=<?php echo js_encode($cat->getTitlelink()) ;?>" title="<?php echo gettext("View category"); ?>">
				<img src="images/view.png" alt="view" />
				</a>
			<?php } ?>
			</div>
			<div class="page-list_icon"><a
					href="?hitcounter=1&amp;id=<?php echo $cat->getID();?>&amp;tab=categories&amp;XSRFToken=<?php echo getXSRFToken('hitcounter')?>"
					title="<?php echo gettext("Reset hitcounter"); ?>"> <img
					src="../../images/reset.png"
					alt="<?php echo gettext("Reset hitcounter"); ?>" /> </a>
			</div>
			<div class="page-list_icon"><a
					href="javascript:confirmDelete('admin-categories.php?delete=<?php echo js_encode($cat->getTitlelink()); ?>&amp;tab=categories&amp;XSRFToken=<?php echo getXSRFToken('delete_category')?>',deleteCategory)"
					title="<?php echo gettext("Delete Category"); ?>"><img
					src="../../images/fail.png" alt="<?php echo gettext("Delete"); ?>"
					title="<?php echo gettext("Delete Category"); ?>" /></a>
			</div>
			<div class="page-list_icon"><input class="checkbox" type="checkbox" name="ids[]"
					value="<?php echo  $cat->getID(); ?>"
					onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
			</div>
	</div>
</div>
<?php
}

/**
 * Prints the checkboxes to select and/or show the category of an news article on the edit or add page
 *
 * @param int $id ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 */
function printCategoryCheckboxListEntry($cat,$articleid,$option) {
	$selected = '';
	if(($option != "all") && !$cat->transient && !empty($articleid)) {
		$cat2news = query_single_row("SELECT cat_id FROM ".prefix('news2cat')." WHERE news_id = ".$articleid." AND cat_id = ".$cat->getID());
		if($cat2news['cat_id'] != "") {
			$selected ="checked ='checked'";
		} else {
			$selected ="";
		}
	}
	$catname = $cat->getTitle();
	$catlink = $cat->getTitlelink();
	if($cat->isProtected() && ((getOption('gallery_security') != 'private'))) {
		$protected = '<img src="'.WEBPATH.'/'.ZENFOLDER.'/images/lock.png" alt="'.gettext('password protected').'" />';
	} else {
		$protected = '';
	}
	$catid = $cat->getID();
	echo "<label for='cat".$catid."'><input name='cat".$catid."' id='cat".$catid."' type='checkbox' value='".$catid."' ".$selected." />".$catname." ".$protected."</label>\n";
}

/**
 * Prints the dropdown menu for the category selector for the news articles list
 *
 */
function printCategoryDropdown() {
	$currentpage = getCurrentAdminNewsPage();
	$result = getAllCategories();
	if(isset($_GET['date'])) {
		$datelink = "&amp;date=".$_GET['date'];
		$datelinkall = "?date=".$_GET['date'];
	} else {
		$datelink = "";
		$datelinkall ="";
	}
?>
	<form name ="AutoListBox2" style="float:left" action="#" >
	<select name="ListBoxURL" size="1" onchange="gotoLink(this.form)">
<?php
if(!isset($_GET['category'])) {
	$selected = "selected='selected'";
} else {
	$selected ="";
}
echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(false,true,true)."'>".gettext("All categories")."</option>";

foreach ($result as $cat) {
	$catobj = new ZenpageCategory($cat['titlelink']);
	// check if there are articles in this category. If not don't list the category.
	$count = countArticles($catobj->getTitlelink(),'all',true);
	$count = " (".$count.")";
	if(isset($_GET['category']) AND $_GET['category'] === $cat['title']) {
		$selected = "selected='selected'";
	} else {
		$selected ="";
	}
	//This is much easier than hacking the nested list function to work with this
	$getparents = $catobj->getParents();
	$levelmark ='';
	foreach($getparents as $parent) {
		$levelmark .= '&raquo; ';
	}
	if ($count != " (0)") {
		echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage."&amp;category=".$catobj->getTitlelink().getNewsAdminOptionPath(false,true,true)."'>".$levelmark.$catobj->getTitle().$count."</option>\n";
	}
}
?>
		</select>
		<script language="JavaScript" type="text/javascript" >
			// <!-- <![CDATA[
			function gotoLink(form) {
			var OptionIndex=form.ListBoxURL.selectedIndex;
			parent.location = form.ListBoxURL.options[OptionIndex].value;}
			// ]]> -->
	</script>
	</form>
<?php
}

/**************************
/* General functions
***************************/

/**
 * Prints the nested list for pages and categories
 *
 * @param string $listtype 'cats-checkboxlist' for a fake nested checkbock list of categories for the news article edit/add page
 * 												'cats-sortablelist' for a sortable nested list of categories for the admin categories page
 * 												'pages-sortablelist' for a sortable nested list of pages for the admin pages page
 * @param int $articleid Only for $listtype = 'cats-checkboxlist': For ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option Only for $listtype = 'cats-checkboxlist': "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 * @return string | bool
 */
function printNestedItemsList($listtype='cats-sortablelist',$articleid='',$option='') {
	switch($listtype) {
		case 'cats-checkboxlist':
		default:
			$ulclass = "";
			break;
		case 'cats-sortablelist':
		case 'pages-sortablelist':
			$ulclass = " class=\"page-list\"";
			break;
	}
	switch($listtype) {
		case 'cats-checkboxlist':
		case 'cats-sortablelist':
			$items = getAllCategories();
			break;
		case 'pages-sortablelist':
			$items = getPages(false);
			break;
		default:
			$items = array();
			break;
	}
	$indent = 1;
	$open = array(1=>0);
	$rslt = false;
	foreach ($items as $item) {
		switch($listtype) {
			case 'cats-checkboxlist':
			case 'cats-sortablelist':
				$itemobj = new ZenpageCategory($item['titlelink']);
				$ismypage = true; // categories are not assigned to backend rights
				break;
			case 'pages-sortablelist':
				$itemobj = new ZenpagePage($item['titlelink']);
				$ismypage = $itemobj->isMyItem(ZENPAGE_PAGES_RIGHTS);
				break;
		}
		$itemsortorder = $itemobj->getSortOrder();
		$itemid =  $itemobj->getID();
		if($ismypage) {
			$order = explode('-', $itemsortorder);
			$level = max(1,count($order));
			if ($toodeep = $level>1 && $order[$level-1] === '') {
				$rslt = true;
			}
			if ($level > $indent) {
				echo "\n".str_pad("\t",$indent,"\t")."<ul".$ulclass.">\n";
				$indent++;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				while ($indent > $level) {
					$open[$indent]--;
					$indent--;
					echo "</li>\n".str_pad("\t",$indent,"\t")."</ul>\n";
				}
			} else { // indent == level
				if ($open[$indent]) {
					echo str_pad("\t",$indent,"\t")."</li>\n";
					$open[$indent]--;
				} else {
					echo "\n";
				}
			}
			if ($open[$indent]) {
				echo str_pad("\t",$indent,"\t")."</li>\n";
				$open[$indent]--;
			}
			switch($listtype) {
				case 'cats-checkboxlist':
					echo "<li>\n";
					printCategoryCheckboxListEntry($itemobj,$articleid,$option);
					break;
				case 'cats-sortablelist':
					echo str_pad("\t",$indent-1,"\t")."<li id=\"id_".$itemid."\" class=\"clear-element page-item1 left\">";
					printCategoryListSortableTable($itemobj,$toodeep);
					break;
				case 'pages-sortablelist':
					echo str_pad("\t",$indent-1,"\t")."<li id=\"id_".$itemid."\">";
					printPagesListTable($itemobj, $toodeep);
					break;
			}
			$open[$indent]++;
		}
	}
	while ($indent > 1) {
		echo "</li>\n";
		$open[$indent]--;
		$indent--;
		echo str_pad("\t",$indent,"\t")."</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
	} else {
		echo "\n";
	}
	return $rslt;
}


/**
 * Updates the sortorder of the items list in the database
 *
 * @param string $mode 'pages' or 'categories'
 * @param array $reports The success messagees
 * @return array
 */
function updateItemSortorder($mode='pages', &$reports) {
	if(!empty($_POST['order'])) { // if someone didn't sort anything there are no values!
		$order = processOrder($_POST['order']);
		$parents = array('NULL');
		foreach ($order as $id=>$orderlist) {
			$id = str_replace('id_','',$id);
			$level = count($orderlist);
			$parents[$level] = $id;
			$myparent = $parents[$level-1];
			switch($mode) {
				case 'pages':
					$dbtable = prefix('pages');
					break;
				case 'categories':
					$dbtable = prefix('news_categories');
					break;
			}
			$sql = "UPDATE " . $dbtable . " SET `sort_order` = '".implode('-',$orderlist)."', `parentid`= ".$myparent." WHERE `id`=" . $id;
			query($sql);
		}
	}
	$reports[] = "<br clear=\"all\"><p class='messagebox fade-message'>".gettext("Sort order saved.")."</p>";
}

/**
 * Checks if no title has been provide for items on new item creation
 * @param string $titlefield The title field
 * @param string $type 'page', 'news' or 'category'
 * @return string
 */
function checkForEmptyTitle($titlefield,$type) {
	switch($type) {
		case "page":
			$text = gettext("Untitled page");
		 break;
		case "news":
			$text = gettext("Untitled article");
			break;
		case "category":
			$text = gettext("Untitled category");
			break;
	}
	if($titlefield) {
		$title = strip_tags($titlefield);
	} else {
		$title = "<span style='color:red; font-weight: bold'>".$text."</span>";
	}
	echo $title;
}


/**
 * Publishes a page or news article
 *
 * @param string $option "page" or "news"
 * @param int $id the id of the article or page
 * @return string
 */
function publishPageOrArticle($option,$id) {
	switch ($option) {
		case "news":
			$dbtable = prefix('news');
			break;
		case "page":
			$dbtable = prefix('pages');
			break;
	}
	$show = sanitize_numeric($_GET['publish']);
	if ($show > 1) {
		query('UPDATE '.$dbtable.' SET `show` = "1", `expiredate`=NULL WHERE id = '.$id);
	} else {
		query("UPDATE ".$dbtable." SET `show` = ".$show." WHERE id = ".$id);
	}
}

/**
 * Skips the scheduled publishing by setting the date of a page or article to the current date to publish it immediately
 *
 * @param string $option "page" or "news"
 * @param int $id the id of the article or page
 * @return string
 */
function skipScheduledPublishing($option,$id) {
	switch ($option) {
		case "news":
			$dbtable = prefix('news');
			break;
		case "page":
			$dbtable = prefix('pages');
			break;
	}
	query("UPDATE ".$dbtable." SET `date` = '".date('Y-m-d H:i:s')."', `show`= 1 WHERE id = ".$id);
}

/**
 * Resets the hitcounter for a page, article or category
 *
 * @param string $option "news", "page" or "cat"
 */
function resetPageOrArticleHitcounter($option='') {
	switch ($option) {
		case "news":
			$dbtable = prefix('news');
			break;
		case "page":
			$dbtable = prefix('pages');
			break;
		case "cat":
			$dbtable = prefix('news_categories');
			break;
	}
	$id = sanitize_numeric($_GET['id']);
	if($_GET['hitcounter']) {
		query("UPDATE ".$dbtable." SET `hitcounter` = 0 WHERE id = ".$id);
	}
}


/**
 * Checks if there are hitcounts and if they are displayed behind the news article, page or category title
 *
 * @param string $item The array of the current news article, page or category in the list.
 * @return string
 */
function checkHitcounterDisplay($item) {
	if($item == 0) {
		$hitcount = "";
	} else {
		if($item == 1) {
			$hits = gettext("hit");
		} else {
			$hits = gettext("hits");
		}
		$hitcount = " (".$item." ".$hits.")";
	}
	return $hitcount;
}


/**
 * returns an array of how many pages, articles, categories and news or pages comments we got.
 *
 * @param string $option What the statistic should be shown of: "news", "pages", "categories"
 */
function getNewsPagesStatistic($option) {
	switch($option) {
		case "news":
			$items = getNewsArticles("","");
			$type = gettext("Articles");
			break;
		case "pages":
			$items = getPages(false);
			$type = gettext("Pages");
			break;
		case "categories":
			$type = gettext("Categories");
			$cats = getAllCategories();
			$total = count($cats);
			$unpub = 0;
			break;
	}
	if($option == "news" OR $option == "pages") {
		$total = count($items);
		$pub = 0;
		foreach($items as $item) {
			switch ($option) {
				case "news":
					$itemobj = new ZenpageNews($item['titlelink']);
					break;
				case "pages":
					$itemobj = new ZenpagePage($item['titlelink']);
					break;
				case "categories":
					$itemobj = new ZenpageCategory($item['titlelink']);
					break;
			}
			$show = $itemobj->getShow();
			if($show == 1) {
				$pub++;
			}
		}
		//echo " (un-published: ";
		$unpub = $total - $pub;
	}
	return array($total,$type,$unpub);
}

function printPagesStatistic() {
	list($total,$type,$unpub) = getNewsPagesStatistic("pages");
	if (empty($unpub)) {
		printf(ngettext('(<strong>%1$u</strong> page)','(<strong>%1$u</strong> pages)',$total),$total);
	} else {
		printf(ngettext('(<strong>%1$u</strong> page, <strong>%2$u</strong> un-published)','(<strong>%1$u</strong> pages, <strong>%2$u</strong> un-published)',$total),$total,$unpub);
	}
}
function printNewsStatistic() {
	list($total,$type,$unpub) = getNewsPagesStatistic("news");
	if (empty($unpub)) {
		printf(ngettext('(<strong>%1$u</strong> news)','(<strong>%1$u</strong> news)',$total),$total);
	} else {
		printf(ngettext('(<strong>%1$u</strong> news, <strong>%2$u</strong> un-published)','(<strong>%1$u</strong> news, <strong>%2$u</strong> un-published)',$total),$total,$unpub);
	}
}
function printCategoriesStatistic() {
	list($total,$type,$unpub) = getNewsPagesStatistic("categories");
	printf(ngettext('(<strong>%1$u</strong> category)','(<strong>%1$u</strong> categories)',$total),$total);
}

/**
 * Prints the links to JavaScript and CSS files zenpage needs.
 * Actually the same as for zenphoto but with different paths since we are in the plugins folder.
 *
 * @param bool $sortable set to true for tabs with sorts.
 *
 */
function zenpageJSCSS() {
	?>
	<link rel="stylesheet" href="zenpage.css" type="text/css" />
	<script type="text/javascript">
		// <!-- <![CDATA[
		$(document).ready(function(){
			$("#tip a").click(function() {
				$("#tips").toggle("slow");
			});
		});
		// ]]> -->
	</script>
<?php
}


function printZenpageIconLegend() { ?>
	<ul class="iconlegend">
	<?php
	if (getOption('gallery_security') != 'private') {
		?>
		<li><img src="../../images/lock.png" alt="" /><?php echo gettext("Has Password"); ?></li>	<li><img src="../../images/pass.png" alt="" /><img	src="../../images/action.png" alt="" /><img src="images/clock.png" alt="" /><?php echo gettext("Published/Not published/Scheduled for publishing"); ?></li>
		<?php
	}
	?>
	<li><img src="../../images/comments-on.png" alt="" /><img src="../../images/comments-off.png" alt="" /><?php echo gettext("Comments on/off"); ?></li>
	<li><img src="../../images/view.png" alt="" /><?php echo gettext("View"); ?></li>
	<li><img src="../../images/reset.png" alt="" /><?php echo gettext("Reset hitcounter"); ?></li>
	<li><img src="../../images/fail.png" alt="" /><?php echo gettext("Delete"); ?></li>
	</ul>
<?php
}

/**
 * Prints a dropdown to select the author of a page or news article (Admin rights users only)
 *
 * @param string $currentadmin The current admin is selected if adding a new article, otherwise the original author
 */
function authorSelector($author=NULL) {
	global $_zp_authority,$_zp_current_admin_obj;
	if (empty($author)) {
		$author = $_zp_current_admin_obj->getUser();
	}
	?>
	<select size='1' name="author" id="author">
	<?php
	if (zp_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
		$admins = $_zp_authority->getAdministrators();
		foreach($admins as $admin) {
			if($admin['rights'] & (ADMIN_RIGHTS | ZENPAGE_PAGES_RIGHTS)) {
				if($author == $admin['user']) {
					echo "<option selected='selected' value='".$admin['user']."'>".$admin['user']."</option>";
				} else {
					echo "<option value='".$admin['user']."'>".$admin['user']."</option>";
				}
			}
		}
	} else {
		?>
		<option selected='selected' value='<?php echo $author; ?>'><?php echo $author; ?></option>"
		<?php
	}
?>
</select>
<?php
}

/**
 * Checks if a page or articles has an expiration date set and prints out this date and a message about it or if it already is expired
 *
 * @param string $object Object of the page or news article to check
 * @return string
 */
function checkIfExpires($object) {
	$dt = $object->getExpireDate();
	if(!empty($dt)) {
		$expired = $dt < date('Y-m-d H:i:s');
		echo "<br /><small>";
		if ($expired) {
			echo '<strong class="expired">';printf(gettext('Expired: %s'),$dt); echo "</strong>";
		} else {
			echo '<strong class="expiredate">';printf(gettext("Expires: %s"),$dt); echo "</strong>";
		}
		echo "</small>";
	}
}

/**
 * Checks if a page or articles is scheduled for publishing and prints out a message and the future date or the publishing date if not scheduled.
 *
 * @param string $object Object of the page or news article to check
 * @return string
 */
function checkIfScheduled($object) {
	$dt = $object->getDateTime();
	if($dt > date('Y-m-d H:i:s')) {
		if($object->getShow() != 1) {
			echo '<strong class="inactivescheduledate">'.$dt.'</strong>';
		} else {
			echo '<strong class="scheduledate">'.$dt.'</strong>';
		}
	} else {
		echo $dt;
	}
}

/**
 * Prints the publish/un-published/scheduled publishing icon with a link for the pages and news articles list.
 *
 * @param string $object Object of the page or news article to check
 * @return string
 */
function printPublishIconLink($object,$type) {
	$urladd1 = "";$urladd2 = "";$urladd3 = "";
	if($type == "news") {
		if(isset($_GET['page'])) { $urladd1 = "&amp;page=".$_GET['page']; }
		if(isset($_GET['date'])) { $urladd2 = "&amp;date=".$_GET['date']; }
		if(isset($_GET['category'])) { $urladd3 = "&amp;category=".$_GET['category']; }
	}
	if ($object->getDateTime() > date('Y-m-d H:i:s')) {
		if ($object->getShow()) {
			$title = gettext("Publish immediately (skip scheduling)");
			?>
			<a href="?skipscheduling=1&amp;id=<?php echo $object->getID().$urladd1.$urladd2.$urladd3; ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>" title="<?php echo $title; ?>">
			<img src="images/clock.png" alt="<?php gettext("Scheduled for published"); ?>" title="<?php echo $title; ?>" /></a>
			<?php
		} else {
			$title = gettext("Enable scheduled publishing");
			?>
			<a href="?publish=1&amp;id=<?php echo $object->getID().$urladd1.$urladd2.$urladd3; ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>" title="<?php echo $title; ?>">
			<img src="../../images/action.png" alt="<?php echo gettext("Un-published"); ?>" title="<?php echo $title; ?>" /></a>
			<?php
		}
	} else {
		if ($object->getShow()) {
			$title = gettext("Un-publish");
			?>
			<a href="?publish=0&amp;id=<?php echo $object->getID().$urladd1.$urladd2.$urladd3; ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>" title="<?php echo $title; ?>">
			<img src="../../images/pass.png" alt="<?php echo gettext("Published"); ?>" title="<?php echo $title; ?>" /></a>
			<?php
		} else {
			$dt = $object->getExpireDate();
			if(empty($dt)) {
				$title = gettext("Publish");
				?>
				<a href="?publish=1&amp;id=<?php echo $object->getID().$urladd1.$urladd2.$urladd3; ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>">
				<?php
			} else {
				$title = gettext("Publish (override expiration)");
				?>
				<a href="?publish=2&amp;id=<?php echo $object->getID().$urladd1.$urladd2.$urladd3; ?>&amp;XSRFToken=<?php echo getXSRFToken('update')?>">
				<?php
			}
			?>
			<img src="../../images/action.png" alt="<?php echo gettext("Un-published"); ?>" title= "<?php echo $title; ?>" /></a>
			<?php
		}
	}
}

/**
 * Checks if a checkbox is selected and checks it if.
 *
 * @param string $field the array field of an item array to be checked (for example "permalink" or "comments on")
 */
function checkIfChecked($field) {
	if ($field) {
		echo 'checked="checked"';
	}
}

/**
 * Checks if the current logged in admin user is the author that locked the page/article.
 * Only that author or any user with admin rights will be able to edit or unlock.
 *
 * @param object $page The array of the page or article to check
 * @return bool
 */
function checkIfLockedPage($page) {
	if (zp_loggedin(ADMIN_RIGHTS)) return true;
	if($page->getLocked()) {
		 return $page->isMyItem(ZENPAGE_PAGES_RIGHTS);
	} else {
		return true;
	}
}

/**
 * Checks if the current logged in admin user is the author that locked the article.
 * Only that author or any user with admin rights will be able to edit or unlock.
 *
 * @param object $page The array of the page or article to check
 * @return bool
 */
function checkIfLockedNews($news) {
	if (zp_loggedin(ADMIN_RIGHTS)) return true;
	if($news->getLocked()) {
		 return $news->isMyItem(ZENPAGE_NEWS_RIGHTS);
	} else {
		return true;
	}
}

/**
 * Checks if the current admin-edit.php page is called for news articles or for pages.
 *
 * @param string $page What you want to check for, "page" or "newsarticle"
 * @return bool
 */
function is_AdminEditPage($page) {
	switch ($page) {
		case "page":
			if(isset($_GET['page'])) {
				return TRUE;
			} else {
				return FALSE;
			}
			break;
		case "newsarticle":
			if(isset($_GET['newsarticle'])) {
				return TRUE;
			} else {
				return FALSE;
			}
			break;
		case "category":
			if(isset($_GET['category'])) {
				return TRUE;
			} else {
				return FALSE;
			}
			break;
	}
}

/**
 * Processes the check box bulk actions
 *
 */
function processZenpageBulkActions($type,&$reports) {
	if (isset($_POST['ids'])) {
		//echo "action for checked items:". $_POST['checkallaction'];
		$action = sanitize($_POST['checkallaction']);
		$ids = $_POST['ids'];
		$total = count($ids);
		$message = NULL;
		$sql = '';
		switch($type) {
			case 'pages':
				$dbtable = prefix('pages');
				break;
			case 'news':
				$dbtable = prefix('news');
				break;
			case 'newscategories':
				$dbtable = prefix('news_categories');
				break;
		}
		if($action != 'noaction') {
			if ($total > 0) {
				$n = 0;
				switch($action) {
					case 'deleteall':
						$message = gettext('Selected items deleted');
						break;
					case 'showall':
						$sql = "UPDATE ".$dbtable." SET `show` = 1 WHERE ";
						$message = gettext('Selected items published');
						break;
					case 'hideall':
						$sql = "UPDATE ".$dbtable." SET `show` = 0 WHERE ";
						$message = gettext('Selected items unpublished');
						break;
					case 'commentson':
						$sql = "UPDATE ".$dbtable." SET `commentson` = 1 WHERE ";
						$message = gettext('Comments enabled for selected items');
						break;
					case 'commentsoff':
						$sql = "UPDATE ".$dbtable." SET `commentson` = 0 WHERE ";
						$message = gettext('Comments disabled for selected items');
						break;
					case 'resethitcounter':
						$sql = "UPDATE ".$dbtable." SET `hitcounter` = 0 WHERE ";
						$message = gettext('Hitcounter for selected items');
						break;
				}
				foreach ($ids as $id) {
					if($action == 'deleteall') {
						$result = query_single_row('SELECT * FROM '.$dbtable.' WHERE id = '.$id);
						if($result) {
							switch($type) {
								case 'pages':
									deletePage($result['titlelink']);
									break;
								case 'news':
									deleteArticle($result['titlelink']);
									break;
								case 'newscategories':
									deleteCategory($result['titlelink']);
									break;
							}
						}
					} else {
						$n++;
						$sql .= " id='".sanitize_numeric($id)."' ";
						if ($n < $total) $sql .= "OR ";
					}
				}
				if(($type != 'news' || $type != 'pages') && $action != 'deleteall') {
					query($sql);
				}
				if(!is_null($message)) $reports[] = "<p class='messagebox fade-message'>".$message."</p>";
			}
		}
	}
}
?>