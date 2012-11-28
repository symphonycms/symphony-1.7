<?php

	/***
	 *
	 * Symphony web publishing system
	 *
	 * Copyright 2004–2006 Twenty One Degrees Pty. Ltd.
	 *
	 * @version 1.7
	 * @licence https://github.com/symphonycms/symphony-1.7/blob/master/LICENCE
	 *
	 ***/

	$GLOBALS['pageTitle'] = "Comments";

	$current_page = 1;

	if(isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']))
		$current_page = intval($_REQUEST['pg']);

	$where = array();

	$filter = NULL;

	if(isset($_REQUEST['filter']) && $_REQUEST['filter'] != '')
		$filter = $_REQUEST['filter'];

	if($filter){
		$filter_bits = explode("-", $_REQUEST['filter']);

		$filter_type = $filter_bits[0];
		$filter_identifier = $filter_bits[1];

		switch($filter_type){

			case 'entry':
				$where[0] = " WHERE `entry_id` = '$filter_identifier' ";
				$where[1] = " AND t1.`entry_id` = '$filter_identifier' ";
				break;

			case 'section':
				$where[0] = " LEFT JOIN `tbl_entries2sections` as t4 ON tbl_comments.entry_id = t4.entry_id WHERE t4.section_id = '$filter_identifier'";
				$where[1] = " AND t4.section_id = '$filter_identifier' ";
				break;

			case 'spam':
				$where[0] = " WHERE `spam` = 'yes' ";
				$where[1] = " AND t1.`spam` = 'yes' ";
				break;

		}
	}

	$comment_count_total = $DB->fetchVar("count", 0, "SELECT count(tbl_comments.id) as `count` FROM `tbl_comments`" . $where[0]);

	$comment_count_remaining = ($comment_count_total - (($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony')));

	$sql = "SELECT t1.* "
		 . "FROM tbl_comments as t1 "
		 . "LEFT JOIN `tbl_entries` AS t3 ON t1.entry_id = t3.id "
		 . "LEFT JOIN `tbl_entries2sections` as t4 ON t3.id = t4.entry_id "
		 . "LEFT JOIN `tbl_sections` AS t5 ON t4.section_id = t5.id "
		 . "LEFT JOIN `tbl_entries2customfields` AS t6 ON t6.field_id = t5.primary_field AND t4.entry_id = t6.entry_id "
		 . "WHERE 1 " . $where[1]
		 . "GROUP BY t1.`id` "
		 . "ORDER BY t1.id DESC "
		 . "LIMIT " . (($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony')) . ", " . $Admin->getConfigVar('pagination_maximum_rows', 'symphony');

	$comments = $DB->fetch($sql);

	$date = $Admin->getDateObj();

	include_once(TOOLKIT . "/class.entrymanager.php");
	$entryManager = new EntryManager($Admin);

	$latest_entries = $entryManager->fetchEntries(5, true);

	$sections = $DB->Fetch("SELECT * FROM `tbl_sections`");

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "complete-flag":
				$Admin->pageAlert("selected-success", array("Comment(s)", "flagged as spam"));
				break;

			case "complete-unflag":
				$Admin->pageAlert("selected-success", array("Comment(s)", "flagged as not spam"));
				break;

			case "complete-delete":
				$Admin->pageAlert("selected-success", array("Comment(s)", "deleted"));
				break;
		}
	}

	$config_button = ($Admin->authorIsSuper() ? '<a class="configure button" href="'.$Admin->getCurrentPageURL().'settings/" title="Configure comment settings">Configure</a>' : "");


?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
  	<h2><!-- PAGE TITLE --> <?php print $config_button; ?>
				<select name="filter">
					<option value="all">All Comments</option>
					<option value="spam" <?php print ($filter == 'spam' ? ' selected="selected"' : ""); ?>>Spam</option>

<?php 		if(!empty($sections) && is_array($sections)){ ?>
					<optgroup label="Comments in Section">

<?php 			foreach($sections as $s){ ?>
						<option value="section-<?php print $s['id']; ?>"<?php print ($filter == ("section-" . $s['id']) ? ' selected="selected"' : ""); ?>><?php print General::reverse_sanitize($s['name']); ?></option>


<?php
                }
                print "					</optgroup>";
            }
?>

<?php 		if(!empty($latest_entries) && is_array($latest_entries)){ ?>
					<optgroup label="Comments on Entry">

<?php 			foreach($latest_entries as $e){ ?>
						<option value="entry-<?php print $e['id']; ?>"<?php print ($filter == ("entry-" . $e['id']) ? ' selected="selected"' : ""); ?>><?php print General::reverse_sanitize($e['fields'][$e['primary_field']]['value_raw']); ?></option>


<?php
                }
                print "					</optgroup>";
            }
?>
				</select>
  	</h2>
		<table>
			<thead>
				<tr>
					<th scope="col">Comment</th>
					<th scope="col">Date</th>
					<th scope="col">Commenter</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3">
						<select name="with-selected">
							<option>With Selected...</option>
							<option value="spam">Flag as Spam</option>
							<option value="blacklist">Blacklist IP Address</option>
							<option value="clean">Unflag</option>
							<option value="delete">Delete</option>
						</select>
						<input name="action[apply]" type="submit" value="Apply" />
					</td>
				</tr>
			</tfoot>
			<tbody>
<?php

				if(empty($comments)):
?>
                        <tr>
                            <td colspan="3" class="inactive">
								None found.
                            </td>
                        </tr>
<?php
				else:
					$bEven = false;
					$date = $Admin->getDateObj();

					foreach ($comments as $comment) {

						foreach ($comment as $index => $value) {
							$comment[$index] = htmlspecialchars (stripslashes ($value));
						}

						$url = $Admin->getCurrentPageURL() . 'edit/&amp;id=' . $comment['id'];

						extract($comment, EXTR_PREFIX_ALL, "comment");

						$comment_creation_timestamp_gmt = $DB->fetchVar("creation_timestamp_gmt", 0, "SELECT UNIX_TIMESTAMP(creation_date_gmt) as `creation_timestamp_gmt` FROM `tbl_metadata` WHERE `relation_id` = '$comment_id' AND `class` = 'comment' LIMIT 1");

						$comment_body = strip_tags(General::reverse_sanitize(General::reverse_sanitize($comment_body)));
						$comment_body_short = General::limitWords($comment_body, 75);

						if(strlen($comment_body_short) < strlen($comment_body)) $comment_body_short .= "...";

						$class = "";
						if(isset($_REQUEST['_f']) && $_REQUEST['id'] == $comment['id']) $class = "active ";

		        		if($bEven) $class .= "even";
		        		$class = trim($class);
?>

				<tr<?php print ($class ? ' class="'.$class.'"' : ""); ?>>
					<td><a class="comment<?php print ($comment_spam == 'yes' ? "-spam" : ""); ?>" href="<?php print $Admin->getCurrentPageURL(); ?>edit/&amp;id=<?php print $comment_id; ?>"<?php print ($comment_body_short != $comment_body ? ' title="'.$comment_body.'"' : ""); ?>">
					<?php print $comment_body_short; ?></a></td>
					<td><?php print $date->get(true, true, $comment_creation_timestamp_gmt); ?></td>
					<td><?php print (empty($comment_author_url) ? $comment_author_name : "<a href=\"".General::validateUrl($comment_author_url) . "\" title=\"$comment_author_name's website\">$comment_author_name</a>"); ?> <input name="items[<?php print $comment_id; ?>]" type="checkbox" /></td>
				</tr>

<?php

						$bEven = !$bEven;
					}

				endif;
?>

			</tbody>
		</table>
<?php


	$pagination = array(
			'total_records' => $comment_count_total,
			'total_pages' => max(ceil($comment_count_total * (1 / $Admin->getConfigVar('pagination_maximum_rows', 'symphony'))), 1),
			'current_page' => $current_page,
			'viewing_start' => max(1, ($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony') + 1),
			'viewing_end' => min($current_page * $Admin->getConfigVar('pagination_maximum_rows', 'symphony'), $comment_count_total),
			'next_page' => ($comment_count_remaining > $Admin->getConfigVar('pagination_maximum_rows', 'symphony') ? ($current_page + 1) : NULL),
			'previous_page' => ($current_page > 1 ? ($current_page - 1) : NULL),
			'first_page' => ($current_page > 1 ? 1 : NULL),
			'last_page' => ($comment_count_remaining > $Admin->getConfigVar('pagination_maximum_rows', 'symphony') ? max(ceil($comment_count_total * (1 / $Admin->getConfigVar('pagination_maximum_rows', 'symphony'))), 1) : NULL)

		);

	extract($pagination);

	if($filter){
		$filter = '&amp;filter='.$filter;
	}

	if($total_pages > 1):
?>

		<ul class="page">
			<li>
<?php

	if($first_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;pg=1">First</a>' . CRLF;

	else
		print '				First';
?>
			</li>

			<li>
<?php

	if($previous_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;pg='.$previous_page.'">&larr; Previous</a>' . CRLF;

	else
		print '				&larr; Previous';
?>
			</li>
			<li title="Viewing <?php print $viewing_start; ?> - <?php print $viewing_end; ?> of <?php print $total_records; ?> comments">Page <?php print $current_page; ?> of <?php print $total_pages; ?></li>

			<li>
<?php

	if($next_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;pg='.$next_page.'">Next &rarr;</a>' . CRLF;

	else
		print '				Next &rarr;';
?>
			</li>
			<li>
<?php

	if($last_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;pg='.$last_page.'">Last</a>' . CRLF;

	else
		print '				Last';
?>
			</li>
		</ul>
<?php
	endif;
?>
	</form>
