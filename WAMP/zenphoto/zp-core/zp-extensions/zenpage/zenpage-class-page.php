<?php
/**
 * zenpage page class
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */

class ZenpagePage extends ZenpageItems {

	var $manage_rights = MANAGE_ALL_PAGES_RIGHTS;
	var $view_rights = VIEW_PAGES_RIGHTS;

	function ZenpagePage($titlelink) {
		$new = parent::PersistentObject('pages', array('titlelink'=>$titlelink), NULL, true, empty($titlelink));
	}

	/**
	 * Returns the sort order
	 *
	 * @return string
	 */
	function getSortOrder() { return $this->get('sort_order'); }

	/**
	 * Stores the sort order
	 *
	 * @param string $sortorder image sort order
	 */
	function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }

	/**
	 * Returns the guest user
	 *
	 * @return string
	 */
	function getUser() { return $this->get('user');	}

	/**
	 * Sets the guest user
	 *
	 * @param string $user
	 */
	function setUser($user) { $this->set('user', $user);	}

	/**
	 * Returns the password
	 *
	 * @return string
	 */
	function getPassword() { return $this->get('password'); }

	/**
	 * Sets the encrypted password
	 *
	 * @param string $pwd the cleartext password
	 */
	function setPassword($pwd) {
		global $_zp_authority;
		if (empty($pwd)) {
			$this->set('password', "");
		} else {
			$this->set('password', $_zp_authority->passwordHash($this->get('user'), $pwd));
		}
	}

	/**
	 * Returns the password hint
	 *
	 * @return string
	 */
	function getPasswordHint() {
		return get_language_string($this->get('password_hint'));
	}

	/**
	 * Sets the password hint
	 *
	 * @param string $hint the hint text
	 */
	function setPasswordHint($hint) { $this->set('password_hint', $hint); }

	/**
	 * duplicates an article
	 * @param string $newtitle the title for the new article
	 */
	function copy($newtitle) {
		$newID = $newtitle;
		$id = parent::copy(array('titlelink'=>$newID));
		if (!$id) {
			$newID = $newtitle.':'.seoFriendly(date('Y-m-d_H-i-s'));
			$id = parent::copy(array('titlelink'=>$newID));
		}
		if ($id) {
			$newobj = new ZenpagePage($newID);
			$newobj->setTitle($newtitle);
			$newobj->setSortOrder(NULL);
			$newobj->setTags($this->getTags());
			$newobj->save();
			return $newobj;
		}
		return false;
	}

	/**
	 * Deletes a page (and also if existing its subpages) from the database
	 *
	 */
	function remove() {
		if ($success = parent::remove()) {
			$sortorder = $this->getSortOrder();
			if ($this->id) {
				$success = $success && query("DELETE FROM " . prefix('obj_to_tag') . "WHERE `type`='pages' AND `objectid`=" . $this->id);
				//	remove subpages
				$mychild = strlen($sortorder)+4;
				$result = query_full_array('SELECT * FROM '.prefix('pages')." WHERE `sort_order` like '".$sortorder."-%'");
				if (is_array($result)) {
					foreach ($result as $row) {
						if (strlen($row['sort_order']) == $mychild) {
							$subpage = new ZenpagePage($row['titlelink']);
							$success = $success && $subpage->remove();
						}
					}
				}
			}
		}
		return $success;
	}

/**
 * Gets the parent pages recursivly to the page whose parentid is passed or the current object
 *
 * @param int $parentid The parentid of the page to get the parents of
 * @param bool $initparents
 * @return array
 */
	function getParents(&$parentid='',$initparents=true) {
		global $parentpages;
		$allitems = getPages();
		if($initparents) {
			$parentpages = array();
		}
		if(empty($parentid)) {
			$currentparentid = $this->getParentID();
		} else {
			$currentparentid = $parentid;
		}
		foreach($allitems as $item) {
			$obj = new ZenpagePage($item['titlelink']);
			$itemtitlelink = $obj->getTitlelink();
			$itemid = $obj->getID();
			$itemparentid = $obj->getParentID();
			if($itemid == $currentparentid) {
				array_unshift($parentpages,$itemtitlelink);
				$obj->getParents($itemparentid,false);
			}
		}
		return $parentpages;
	}


/**
 * Gets the sub pages recursivly by titlelink
  * @param $all set true for all sub page levels (default) or false for only the direct sub level
 * @return array
 */
	function getSubPages() {
		$subpages = array();
		$sortorder = $this->getSortOrder();
		$pages = getPages();
		//echo "<pre>"; print_r($pages); echo "</pre>";
		foreach($pages as $page) {
			$pageobj = new ZenpagePage($page['titlelink']);
			$hasSortorder = strstr($pageobj->getSortOrder(),$sortorder);
			if($hasSortorder && $pageobj->getSortOrder()  != $sortorder) { // exclude the category itself!
				array_push($subpages,$pageobj->getTitlelink());
			}
		}
		if(count($subpages) != 0) {
			return $subpages;
		} else {
			return FALSE;
		}
	}

	/**
	 * Checks if user is allowed access to the page
	 * @param $hint
	 * @param $show
	 */
	function checkAccess(&$hint, &$show) {
		$hint = $show = '';
		if ($this->isMyItem(LIST_RIGHTS)) return true;
		if (getOption('gallery_security') == 'private') {	// only registered users allowed
			return false;
		}
		return $this->checkforGuest($hint, $show);
	}

	/**
	 * Checks if user is allowed to access the page
	 * @param $hint
	 * @param $show
	 */
	function checkforGuest(&$hint, &$show) {
		$hint = $show = '';
		$pageobj = $this;
		$hash = $pageobj->getPassword();
		while(empty($hash) && !is_null($pageobj)) {
			$parentID = $pageobj->getParentID();
			if (empty($parentID)) {
				$pageobj = NULL;
			} else {
				$sql = 'SELECT `titlelink` FROM '.prefix('pages').' WHERE `id`='.$parentID;
				$result = query_single_row($sql);
				$pageobj = new ZenpagePage($result['titlelink']);
				$hash = $pageobj->getPassword();
			}
		}
		if (empty($hash)) { // no password required
			return 'zp_unprotected';
		} else {
			$authType = "zp_page_auth_" . $pageobj->get('id');
			$saved_auth = zp_getCookie($authType);
			if ($saved_auth == $hash) {
				return $authType;
			} else {
				$user = $pageobj->getUser();
				$show = (!empty($user));
				$hint = $pageobj->getPasswordHint();
				return false;
			}
		}
		//return checkPagePassword($this, $hint, $show) != 'zp_unprotected';
	}

/**
 * Checks if a page is protected and returns TRUE or FALSE
 * NOTE: This function does only check if a password is set not if it has been entered! Use $this->checkforGuest() for that.
 *
 * @return bool
 */
	function isProtected() {
		$hint = $show = '';
		return $this->checkforGuest($hint, $show) != 'zp_unprotected';
	}

	/**
	 * Checks if user is author of page
	 * @param bit $action what the caller wants to do
	 *
	 * returns true of access is allowed
	 */
	function isMyItem($action) {
		global $_zp_current_admin_obj;
		if (parent::isMyItem($action)) {
			return true;
		}
		if (zp_apply_filter('check_credentials', false, $this, $action)) return true;
		if (zp_loggedin($action)) {
			$mypages = $_zp_current_admin_obj->getObjects('pages');
			if (!empty($mypages)) {
				if (array_search($this->getTitlelink(),$mypages)!==false) return true;
			}
			return $_zp_current_admin_obj->getUser() == $this->getAuthor();
		}
		return false;
	}
}
?>