<?php

	###
	#
	#  Symphony web publishing system
	# 
	#  Copyright 2004 - 2006 Twenty One Degrees Pty. Ltd. This code cannot be
	#  modified or redistributed without permission.
	#
	#  For terms of use please visit http://21degrees.com.au/products/symphony/terms/
	#
	###

	$EM = new EventManager(array('parent' => &$Admin));

	$oEvent =& $EM->create($_REQUEST['file']);	

	$about = $oEvent->about();

	$GLOBALS['pageTitle'] = 'Events > ' . $about['name'];

	$date = $Admin->getDateObj();
	
	$link = $about['author']['name'];
	
	if(isset($about['author']['website']))
		$link = '<a href="' . General::validateURL($about['author']['website']) . '">' . $about['author']['name'] . '</a>';
		
	elseif(isset($about['author']['email']))
			$link = '<a href="mailto:' . $about['author']['email'] . '">' . $about['author']['name'] . '</a>';
?>


<form id="controller" action="" method="post">
  	<h2><?php print $about['name']; ?></h2>

	<fieldset>
		<dl>
			<dt>Author</dt>
			<dd><?php print $link; ?></dd>
			<dt>Version</dt>
			<dd><?php print $about['version']; ?></dd>
			<dt>Release Date</dt>
			<dd><?php print $date->get(true, true, strtotime($about['release-date'])); ?></dd>		
		</dl>

		<dl class="important">
			<dt>Trigger Condition</dt>
			<dd><code><?php print ($about['trigger-condition'] ? $about['trigger-condition'] : 'None'); ?></code></dd>
			<dd>This is the field name or other condition used to trigger the event.</dd>
			<dt>Recognised Fields</dt>
<?php if(!is_array($about['recognised-fields']) || empty($about['recognised-fields'])){ ?>		
			<dd><code>None</code></dd>
<?php }else{ ?>
			<dd>
				<ul>
<?php 
						foreach($about['recognised-fields'] as $f){
							list($name, $required, $options) = $f;
							
							print '					<li><code>'.$name.'</code>'.($options != NULL ? ' <span>('.$options.')</span>' : '').''.($required == true ? ' *' : '').'</li>';
						}
						
?>
				</ul>
			</dd>
			<dd>Required fields marked with <em>*</em>.</dd>
<?php } ?>
		</dl>

		<p><?php print $about['description']; ?></p>

<?php

	if(is_callable(array($oEvent, "preview"))){
?>

		<h3>Example XML Response</h3>
		<p><pre><code><?php print str_replace("<", "&lt;", $oEvent->preview());?></code></pre></p>

<?php
	}
?>

	</fieldset>
</form>