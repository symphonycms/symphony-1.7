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

	$GLOBALS['pageTitle'] = "Your Campfire Services";

	$tmp = $CampfireManager->listAll();

	$services = array();

	if(is_array($tmp) && !empty($tmp)){
		foreach($tmp as $owner => $list) {

			if(is_array($list) && !empty($list)){
				foreach($list as $s => $about) {
					$services["$owner/$s"] = $about;
				}
			}

		}
	}

	## Sort the results by their status. Disabled at the bottom.
	uasort($services, array('CampfireManager', 'sortByStatus'));

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "complete-install":
				$Admin->pageAlert("selected-success", array("Service(s)", "installed"));
				break;

			case "complete-uninstall":
				$Admin->pageAlert("selected-success", array("Service(s)", "uninstalled"));
				break;

			case "complete-enable":
				$Admin->pageAlert("selected-success", array("Service(s)", "enabled"));
				break;

			case "complete-disable":
				$Admin->pageAlert("selected-success", array("Service(s)", "disabled"));
				break;

			case "complete-update":
				$Admin->pageAlert("selected-success", array("Service(s)", "updated"));
				break;

			case "complete-hide-from-menu":
				$Admin->pageAlert("selected-success", array("Service(s)", "hidden from the menu"));
				break;

			case "complete-show-in-menu":
				$Admin->pageAlert("selected-success", array("Service(s)", "added to the menu"));
				break;
		}
	}

    $more_button = ($Admin->authorIsSuper() ? '<a href="http://overture21.com" class="more button" title="Get more campfire services">Get More Services</a>' : "");

?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
  	<h2><!-- PAGE TITLE --> <?php print $more_button; ?></h2>
		<table>
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Status</th>
					<th scope="col">Version</th>
					<th scope="col">Author</th>
				</tr>
			</thead>
<?php	if($Admin->authorIsSuper()){ ?>
			<tfoot>
				<tr>
					<td colspan="4">
						<select name="with-selected">
							<option>With Selected...</option>
							<option value="enable">Enable</option>
							<option value="disable">Disable</option>
							<option value="show-in-menu">Show in menu</option>
							<option value="hide-from-menu">Hide from menu</option>
						</select>
						<input name="action[apply]" type="submit" value="Apply" />
					</td>
				</tr>
			</tfoot>
<?php 	}		?>

			<tbody>
<?php
				if(is_array($services) && !empty($services)){
					$bEven = false;

					foreach($services as $key => $detail) {

						$class = ($bEven ? 'even' : '');

						list($owner, $name) = explode('/', $key);
						if($CampfireManager->requiresUpdate($name, $owner)){
							$class = ' highlight';
							$detail['status'] = 'Ready to update';
						}

						if($detail['status'] != 'enabled') $class .= ' inactive';

						$class = trim($class);

						$link = $detail['author']['name'];

						if(isset($detail['author']['website']))
							$link = '<a href="' . General::validateURL($detail['author']['website']) . '">' . $detail['author']['name'] . '</a>';

						elseif(isset($detail['author']['email']))
								$link = '<a href="mailto:' . $detail['author']['email'] . '">' . $detail['author']['name'] . '</a>';
?>

				<tr<?php print ($class != '' ? ' class="'.$class.'"' : ''); ?>>
					<td><a href="<?php print URL . "/symphony/?page=/campfire/"; ?><?php print ($detail['status'] == 'enabled' && $detail['panel'] ? "service/$key/" : "info/&amp;name=$key" ); ?>" class="campfire<?php print ($detail['status'] != 'not installed' ? '-installed' : ''); ?>"><?php print $detail['name']; ?></a></td>
					<td><?php print ucwords($detail['status']); ?></td>
					<td><?php print $detail['version']; ?></td>
					<td><?php print $link; ?> <?php if($detail['status'] != 'not installed') print '<input name="items['.$key.']" type="checkbox" />'; ?></td>

				</tr>

<?php
						$bEven = !$bEven;
					}
				}else{
?>
                 <tr><td colspan="4" class="inactive">None found.</td></tr>

<?php
		      }
			?>

			</tbody>
		</table>
	</form>