<?php
/**
 * admin.php is the main script for administrative functions.
 * @package admin
 */

// force UTF-8 Ø

/* Don't put anything before this line! */
define('OFFSET_PATH', 1);

// The login will always occur in this file thanks to printLoginForm() function.
// to implement 'server_protocol' == 'https_admin' we need to redirect to https
// very soon in that file (before auth_zp.php is processed as it might clear
// the cookies)
// When already logged in, auth_zp.php take care of the redirection.
// To retrive getOption function:
require_once(dirname(__FILE__).'/functions-basic.php');
if (getOption('server_protocol') == 'https_admin') {
	// force https login
	if (!isset($_SERVER["HTTPS"])) {
		$redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		header("Location:$redirect");
		exit();
	}
}

require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');
require_once(dirname(__FILE__).'/functions-rss.php');

checkInstall();

$msg = '';
if(getOption('zp_plugin_zenpage')) {
	require_once(dirname(__FILE__).'/'.PLUGIN_FOLDER.'/zenpage/zenpage-admin-functions.php');
}
if (zp_loggedin()) { /* Display the admin pages. Do action handling first. */

	$gallery = new Gallery();
	$gallery->garbageCollect();
	if (isset($_GET['action'])) {
		$rightsneeded = array('external'=>ALL_RIGHTS, 'check_for_update'=>OVERVIEW_RIGHTS);
		$action = $_GET['action'];
		$needs = ADMIN_RIGHTS;
		if (isset($rightsneeded[$action])) {
			$needs = $rightsneeded[$action] | ADMIN_RIGHTS;
		}
		if (zp_loggedin($needs)) {
			switch ($action) {
				/** clear the cache ***********************************************************/
				/******************************************************************************/
				case "clear_cache":
					XSRFdefender('clear_cache');
					$gallery->clearCache();
					$class = 'messagebox';
					$msg = gettext('Image cache cleared.');
					break;

					/** clear the RSScache ***********************************************************/
					/******************************************************************************/
				case "clear_rss_cache":
					XSRFdefender('clear_cache');
					clearRSScache();
					$class = 'messagebox';
					$msg = gettext('RSS cache cleared.');
					break;

					/** Reset hitcounters ***********************************************************/
					/********************************************************************************/
				case "reset_hitcounters":
					XSRFdefender('hitcounter');
					query('UPDATE ' . prefix('albums') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('images') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('news') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('pages') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('news_categories') . ' SET `hitcounter`= 0');
					query('UPDATE ' . prefix('options') . ' SET `value`= 0 WHERE `name` LIKE "Page-Hitcounter-%"');
					query("DELETE FROM ".prefix('plugin_storage')." WHERE `type` = 'rsshitcounter'");
					$class = 'messagebox';
					$msg = gettext('All hitcounters have been set to zero');
					break;

					/** check for update ***********************************************************/
					/********************************************************************************/
				case 'check_for_update':
					$v = checkForUpdate();
					if (empty($v)) {
						$class = 'messagebox';
						$msg = gettext("You are running the latest zenphoto version.");
					} else {
						$class = 'errorbox';
						if ($v == 'X') {
							$msg = gettext("Could not connect to <a href=\"http://www.zenphoto.org\">zenphoto.org</a>");
						} else {
							$msg =  "<a href=\"http://www.zenphoto.org\">". sprintf(gettext("zenphoto version %s is available."), $v)."</a>";
						}
					}
					break;
					//** external script return
				case 'external':
					if (isset($_GET['error'])) {
						$class = 'errorbox';
					} else {
						$class = 'messagebox';
					}
					if (isset($_GET['msg'])) {
						$msg = sanitize($_GET['msg']);
					} else {
						$msg = '';
					}
					break;
			}
		} else {
			$class = 'errorbox';
			$actions = array(	'clear_cache'=>gettext('purge Image cache'),
												'clear_rss_cache'=>gettext('purge RSS cache'),
												'reset_hitcounters'=>gettext('reset all hitcounters'));
			$msg = sprintf(gettext('You do not have proper rights to %s.'),$actions[$action]);
		}
	} else {
		if (isset($_GET['from'])) {
			$class = 'errorbox';
			$msg = sprintf(gettext('You do not have proper rights to access %s.'),html_encode(sanitize($_GET['from'])));
		}
	}

/************************************************************************************/
/** End Action Handling *************************************************************/
/************************************************************************************/

}

if ($_zp_null_account || empty($msg) && zp_loggedin() && !zp_loggedin(OVERVIEW_RIGHTS)) {	// admin access without overview rights, redirect to first tab
	$tab = array_shift($zenphoto_tabs);
	$link = $tab['link'];
	header('location:'.$link);
	exit();
}

// Print our header
printAdminHeader('overview');

echo "\n</head>";
if (!zp_loggedin()) {
	?>
	<body style="background-image: none">
	<?php
} else {
	?>
<body>
<?php }
// If they are not logged in, display the login form and exit

if (!zp_loggedin()) {
	if (isset($_GET['from'])) {
		$from = sanitize($_GET['from']);
		$from = urldecode($from);
	} else {
		$from = urldecode(currentRelativeURL(__FILE__));
	}
	printLoginForm($from);
	echo "\n</body>";
	echo "\n</html>";
	exit();

} else { /* Admin-only content safe from here on. */
	printLogoAndLinks();
	?>
<div id="main">
<?php printTabs(); ?>
<div id="content">
<?php


/*** HOME ***************************************************************************/
/************************************************************************************/

if (!empty($msg)) {
	?>
	<div class="<?php echo $class; ?>" id="fade-message">
		<h2><?php echo $msg; ?></h2>
	</div>
	<?php
}
?>
<div id="overview-leftcolumn">
<?php
if (zp_loggedin(OVERVIEW_RIGHTS)) {
	?>
	<div class="boxouter">
	<div class="box" id="overview-gallerystats">
	<h2 class="h2_bordered"><?php echo gettext("Gallery Stats"); ?></h2>
	<ul>
	<li>
	<?php
	$t = $gallery->getNumImages();
	$c = $t-$gallery->getNumImages(true);
	if ($c > 0) {
		printf(ngettext('<strong>%1$u</strong> Image (%2$u not visible)','<strong>%1$u</strong> Images (%2$u not visible)',$t),$t, $c);
	} else {
		printf(ngettext('<strong>%u</strong> Image','<strong>%u</strong> Images',$t),$t);
	}
	?>
	</li>
	<li>
	<?php
	$t = $gallery->getNumAlbums(true);
	$c = $t-$gallery->getNumAlbums(true,true);
	if ($c > 0) {
		printf(ngettext('<strong>%1$u</strong> Album (%2$u not published)','<strong>%1$u</strong> Albums (%2$u not published)',$t),$t, $c);
	} else {
		printf(ngettext('<strong>%u</strong> Album', '<strong>%u</strong> Albums',$t),$t);
	}
	?>
	</li>
	<li>
	<?php
	$t = $gallery->getNumComments(true);
	$c = $t - $gallery->getNumComments(false);
	if ($c > 0) {
		printf(ngettext('<strong>%1$u</strong> Comment (%2$u in moderation)','<strong>%1$u</strong> Comments (%2$u in moderation)', $t), $t, $c);
	} else {
		printf(ngettext('<strong>%u</strong> Comment','<strong>%u</strong> Comments', $t), $t);
	}
	?>
	</li>
	<?php
	if(getOption('zp_plugin_zenpage')) { ?>
		<li>
			<?php
			list($total,$type,$unpub) = getNewsPagesStatistic("pages");
			if (empty($unpub)) {
				printf(ngettext('<strong>%1$u</strong> Page','<strong>%1$u</strong> Pages',$total),$total,$type);
			} else {
				printf(ngettext('<strong>%1$u</strong> Page (%2$u un-published)','<strong>%1$u</strong> Pages (%2$u un-published)',$total),$total,$unpub);
			}
			?>
		</li>
		<li>
			<?php
			list($total,$type,$unpub) = getNewsPagesStatistic("news");
			if (empty($unpub)) {
				printf(ngettext('<strong>%1$u</strong> News','<strong>%1$u</strong> News',$total),$total);
			} else {
				printf(ngettext('<strong>%1$u</strong> News (%2$u un-published)','<strong>%1$u</strong> News (%2$u un-published)',$total),$total,$unpub);
			}
			?>
		</li>
		<li>
			<?php
			list($total,$type,$unpub) = getNewsPagesStatistic("categories");
			printf(ngettext('<strong>%1$u</strong> Category','<strong>%1$u</strong> Categories',$total),$total);
			?>
		</li>
	<?php
	}
	?>
	</ul>
	</div>
	</div>

	<div class="box" id="overview-info">
	<h2 class="h2_bordered"><?php echo gettext("Installation information"); ?></h2>
	<ul>
	<?php

	if (defined('RELEASE')) {
			$official = gettext('Official Build');
		} else {
			$official = gettext('SVN');
		}
		$graphics_lib = zp_graphicsLibInfo();
		?>
		<li><?php printf(gettext('Zenphoto version <strong>%1$s [%2$s] (%3$s)</strong>'),ZENPHOTO_VERSION,ZENPHOTO_RELEASE,$official); ?></li>
		<li><?php if ($_zp_setupCurrentLocale_result) {
								printf(gettext('Current locale setting: <strong>%1$s</strong>'),$_zp_setupCurrentLocale_result);
							} else {
								echo gettext('<strong>Locale setting has failed</strong>');
							}
		 ?>
		</li>
		<li>
			<?php
			$themes = $gallery->getThemes();
			$currenttheme = $gallery->getCurrentTheme();
			if (array_key_exists($currenttheme, $themes) && isset($themes[$currenttheme]['name'])) {
				$currenttheme = $themes[$currenttheme]['name'];
			}
			printf(gettext('Current gallery theme: <strong>%1$s</strong>'),$currenttheme);
			?>
		</li>
		<li><?php printf(gettext('PHP version: <strong>%1$s</strong>'),phpversion()); ?></li>
		<li><?php printf(gettext("Graphics support: <strong>%s</strong>"),$graphics_lib['Library']); ?></li>
		<li><?php printf(gettext('PHP memory limit: <strong>%1$s</strong> (Note: Your server might allocate less!)'),INI_GET('memory_limit')); ?></li>
		<li>
			<?php
			$dbsoftware = db_software();
			printf(gettext('%1$s version: <strong>%2$s</strong>'),$dbsoftware['application'],$dbsoftware['version']);
			?>

		</li>
		<li><?php printf(gettext('Database name: <strong>%1$s</strong>'),db_name()); ?></li>
		<li>
		<?php
		$prefix = prefix();
		if(!empty($prefix)) {
			echo sprintf(gettext('Table prefix: <strong>%1$s</strong>'),prefix());
		}
		?>
		</li>
		<li><?php printf(gettext('Spam filter: <strong>%s</strong>'), getOption('spam_filter')) ?></li>
		<li><?php printf(gettext('CAPTCHA generator: <strong>%s</strong>'), getOption('captcha')) ?></li>
		<?php
		if (!zp_has_filter('sendmail')) {
			?>
			<li style="color:RED"><?php echo gettext('There is no mail handler configured!'); ?></li>
			<?php
		}
		?>
		</ul>

		<?php
		$plugins = array_keys(getEnabledPlugins());
		$c = count($plugins);
		?>
		<h3><a href="javascript:toggle('plugins_hide');toggle('plugins_show');" ><?php printf(ngettext("%u active plugin:", "%u active plugins:", $c), $c); ?></a></h3>
		<div id="plugins_hide" style="display:none">
			<ul class="plugins">
			<?php
			if ($c > 0) {
				$filters = array();
				natcasesort($plugins);
				foreach ($plugins as $extension) {
					$pluginStream = file_get_contents(getPlugin($extension.'.php'));
					$str =  $pluginStream;
					$i = strpos($str, '$plugin_version');
					if ($i !== false) {
						$str = substr($str, $i);
						//$j = strpos($str, ";\n"); // This is wrong - PHP will not treat all newlines as \n.
						$j = strpos($str, ";"); // This is also wrong; it disallows semicolons in strings. We need a regexp.
						$str = substr($str, 0, $j+1);
						eval($str);
						$version = ' v'.$plugin_version;
					} else {
						$version = '';
					}
					$str = $pluginStream;
					$k = 0;
					do {
						$i = strpos($str, 'zp_register_filter', $k);
						if ($i) {
							$i = strpos($str, '(', $i);
							if ($i) {
								$k = strpos($str,';',$i);
								if ($k) {
									$strp = substr($str,$i,$k-$i);
									$j = strrpos($strp, ')');
									$k++;
									if ($j) {
										$paramsstr = substr($strp, 1,$j-1);
										$params = explode(',', $paramsstr);
										if (array_key_exists(2, $params)) {
											$priority = (int)$params[2];
										} else {
											$priority = 5;
										}
										$filter = unQuote(trim($params[0]));
										$function = unQuote(trim($params[1]));
										$filters[$filter][$priority][$function] = array('function'=>$function,'accepted_args'=>NULL,'script'=>$extension.'.php');
									} else {
										break;
									}
								} else {
									break;
								}
							}
						}
					} while ($i !== false);
					echo "<li>".$extension.$version."</li>";
				}
			} else {
				echo '<li>'.gettext('<em>none</em>').'</li>';
			}
			?>
			</ul>
		</div>
		<div id="plugins_show">
			<br />
		</div>
		<?php
		ksort($filters,SORT_LOCALE_STRING);
		$c = count($filters);
		?>
		<h3><a href="javascript:toggle('filters_hide');toggle('filters_show');" ><?php printf(ngettext("%u active filter:","%u active filters:", $c), $c); ?></a></h3>
		<div id="filters_hide" style="display:none">
			<ul class="plugins">
			<?php
			if ($c > 0) {
				foreach ($filters as $filter=>$array_of_priority) {
					ksort($array_of_priority);
					?>
					<li>
						<em><?php echo $filter; ?></em>
						<ul class="filters">
						<?php
						foreach ($array_of_priority as $priority=>$array_of_filters) {
							foreach ($array_of_filters as $data) {
								?>
								<li><em><?php echo $priority; ?></em>: <?php echo $data['script'] ?> =&gt; <?php echo $data['function'] ?></li>
								<?php
							}
						}
						?>
					</ul>
				</li>
				<?php
				}
			} else {
				?>
				<li><?php echo gettext('<em>none</em>'); ?></li>
				<?php
			}
			?>
			</ul>
		</div>
		<div id="filters_show">
			<br />
		</div>
	</div>

	<?php
	zp_apply_filter('admin_overview', 'left');
}
?>

<br clear="all" />
</div><!-- overview leftcolumn end -->

<div id="overview-rightcolumn">
<?php
if (zp_loggedin(OVERVIEW_RIGHTS)) {
	$buttonlist = array();
	$curdir = getcwd();
	chdir(SERVERPATH . "/" . ZENFOLDER . '/'.UTILITIES_FOLDER.'/');
	$filelist = safe_glob('*'.'php');
	natcasesort($filelist);
	foreach ($filelist as $utility) {
		$button_text = '';
		$button_hint = '';
		$button_icon = '';
		$button_alt = '';
		$button_hidden = '';
		$button_action = UTILITIES_FOLDER.'/'.$utility;
		$button_rights = false;
		$button_enable = true;
		$button_XSRFTag = '';

		$utilityStream = file_get_contents($utility);
		eval(isolate('$button_text', $utilityStream));
		eval(isolate('$button_hint', $utilityStream));
		eval(isolate('$button_icon', $utilityStream));
		eval(isolate('$button_rights', $utilityStream));
		eval(isolate('$button_alt', $utilityStream));
		eval(isolate('$button_hidden', $utilityStream));
		eval(isolate('$button_action', $utilityStream));
		eval(isolate('$button_enable', $utilityStream));
		eval(isolate('$button_XSRFTag', $utilityStream));

		$buttonlist[] = array(
								'XSRFTag'=>$button_XSRFTag,
								'enable'=>$button_enable,
								'button_text'=>$button_text,
								'formname'=>$utility,
								'action'=>$button_action,
								'icon'=>$button_icon,
								'title'=>$button_hint,
								'alt'=>$button_alt,
								'hidden'=>$button_hidden,
								'rights'=> $button_rights  | ADMIN_RIGHTS
		);

	}
	$buttonlist = zp_apply_filter('admin_utilities_buttons', $buttonlist);
	$buttonlist = sortMultiArray($buttonlist, 'button_text', false);
	$count = 0;
	foreach ($buttonlist as $key=>$button) {
		if (zp_loggedin($button['rights'])) {
			$count ++;
		} else {
			unset($buttonlist[$key]);
		}
	}
	$count = round($count/2);
	?>
	<div class="box" id="overview-utility">
	<h2 class="h2_bordered"><?php echo gettext("Utility functions"); ?></h2>
		<div id="overview-maint_l">
		<?php
		foreach ($buttonlist as $button) {
			$button_icon = $button['icon'];
			?>
			<form name="<?php echo $button['formname']; ?>"	action="<?php echo $button['action']; ?>">
				<?php if (isset($button['XSRFTag']) && $button['XSRFTag']) XSRFToken($button['XSRFTag']); ?>
				<?php echo $button['hidden']; ?>
				<div class="buttons">
				<button class="tooltip" type="submit"	title="<?php echo $button['title']; ?>" <?php if (!$button['enable']) echo 'disabled="disabled"'; ?>>
				<?php
				if(!empty($button_icon)) {
					?>
					<img src="<?php echo $button_icon; ?>" alt="<?php echo $button['alt']; ?>" />
					<?php
				}
				echo html_encode($button['button_text']);
				?>
				</button>
				</div>
				<br clear="all" />
			</form>&nbsp;
			<?php
			$count --;
			if (!$count) { // half way through
				?>
				</div>
				<div id="overview-maint_r">
				<?php
			}
		}
		?>
		<br clear="all" />
		</div>
	</div>
	<?php
	zp_apply_filter('admin_overview', 'right');
}
?>

<br clear="all" />
</div><!-- overview rightcolumn end -->
<br clear="all" />
</div><!-- content -->
<br clear="all" />
<?php
printAdminFooter();
} /* No admin-only content allowed after this bracket! */ ?></div>
<!-- main -->
</body>
<?php // to fool the validator
echo "\n</html>";
?>
