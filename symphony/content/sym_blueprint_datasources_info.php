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

	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$oDataSource = $DSM->create($_REQUEST['file']);	
	$about = $oDataSource->about();
	
	$date = $Admin->getDateObj();

	$GLOBALS['pageTitle'] = 'Data Sources > ' . $about['name'];

	$link = $about['author']['name'];
	
	if(isset($about['author']['website']))
		$link = '<a href="' . General::validateURL($about['author']['website']) . '">' . $about['author']['name'] . '</a>';
		
	elseif(isset($about['author']['email']))
			$link = '<a href="mailto:' . $about['author']['email'] . '">' . $about['author']['name'] . '</a>';

?>

<form id="controller" action="/" method="post">
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
			<dt>URL Parameters</dt>
<?php if(!is_array($about['recognised-url-param']) || empty($about['recognised-url-param'])){ ?>		
			<dd><code>None</code></dd>
<?php }else{ ?>
			<dd>
				<ul>
<?php 
						foreach($about['recognised-url-param'] as $f){
							print '					<li><code>'.$f.'</code></li>';
						}

?>
				</ul>
			</dd>
<?php } ?>
		</dl>
		
		<p><?php print $about['description']; ?></p>
<?php

	if(is_callable(array($oDataSource, "example"))){
?>

		<h3>Example XML</h3>
		<p>
			<pre><code>
<?php print str_replace("<", "&lt;", $oDataSource->example());?>
			</code></pre>
		</p>

<?php
	}
?>
	</fieldset>
</form>
