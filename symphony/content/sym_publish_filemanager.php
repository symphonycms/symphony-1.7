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

	if($Admin->getConfigVar('enabled', 'filemanager') != 'yes'){
		$Admin->fatalError('Access Denied', '<p>Access denied. The file manager for this site has been disabled.</p>', true, true);
		die();		
	}

	$GLOBALS['pageTitle'] = "File Manager";
	
	$current_page = 1;
	
	if(isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']))
		$current_page = intval($_REQUEST['pg']);
	
	$ignore = array("events", "data-sources", "text-formatters", "pages", "masters", "utilities");
	
	$path = '/workspace/';	
		
	if(isset($_REQUEST['filter'])){
		$path = $_REQUEST['filter'];
		$path = '/workspace/' . trim($path, '/') . '/';
	}
	
	$limitFilesToTypes = array();	
		
	if(!$Admin->authorIsSuper()) $limitFilesToTypes = preg_split('@\s*,\s*@i', $Admin->getConfigVar('filetype_restriction', 'filemanager'), -1, PREG_SPLIT_NO_EMPTY);	
		
	$files = General::listStructureFlat(DOCROOT . $path, $limitFilesToTypes, false, "asc", WORKSPACE);
	
	$files_count_total = count($files);
	$files_count_remaining = ($files_count_total - (($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony')));
		
	$files = array_slice($files, ($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony'), $Admin->getConfigVar('pagination_maximum_rows', 'symphony'));	
		  
	$directories = General::listDirStructure(WORKSPACE, true, "asc", DOCROOT);
    
	$date = new SymDate($Admin->getConfigVar("time_zone", "region"), $Admin->getConfigVar("date_format", "region"));

	if(isset($_GET['_f'])){
		switch($_GET['_f']){
						
					
			case "upload-fail":
				$Admin->pageAlert("upload-fail", NULL, false, 'error');				
				break;	
										
			case "upload-success":
				$Admin->pageAlert("upload-success", NULL, false, 'error');	
				break;
				
			case "deleted":
				$Admin->pageAlert("selected-success", array("File(s)", "deleted"), false, 'error');
				break;															
		}
	}

?>
	<form method="post" action="<?php print $Admin->getCurrentPageURL(); ?>" enctype="multipart/form-data">
		<h2>File Manager <a class="upload button"  href="#upload" title="Upload a file">Upload</a>
			<select name="filter">
				<option value="">workspace/</option>
<?php

			foreach($directories as $d){
				$d_clean = str_replace('/workspace', '', $d);
				$d_clean = trim($d_clean, '/');	
				
				if(!in_array($d_clean, $ignore)){ 
?>						
                		<option value="<?php print str_replace('/workspace', '', $d); ?>"<?php print ($path == $d ? ' selected="selected"' : ""); ?>><?php print ltrim($d, '/'); ?></option>
<?php
				}
			}
?>	

			</select>
		</h2>
		<table>
			<thead>
				<tr>
					<th scope="col">File Name</th>
					<th scope="col">Location</th>
					<th scope="col">Size</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3">
						<select name="with-selected">
							<option>With Selected...</option>
							<option value="delete">Delete</option>
						</select>
						<input name="action[apply]" type="submit" value="Apply" />
					</td>
				</tr>
			</tfoot>
			<tbody>

<?php
			
		if(!empty($files) && is_array($files)):
			$bEven = false;
	
			foreach($files as $row) {

				$handle = "/" . trim($row['path'], "/") . "/" . $row['name'];

				$handle = preg_replace('/\/{2,}/', "/", $handle);

				$abs_filename = WORKSPACE . $handle;
				$filename = URL . "/workspace" . $handle;

				$file_cell_handle = str_replace($row['name'], "", $handle);

				$file_cell_handle = "/" . ltrim( $file_cell_handle, "/");

				$file_size = General::formatFilesize(filesize($abs_filename)); 

				$downloads = $recorded_files[$handle]['downloads']; 

				if(empty($downloads)) $downloads = 0;
				else $downloads = "<strong>$downloads</strong>";

?>
				<tr<?php print ($bEven ? ' class="even"' : ""); ?>>
					<td><a href="<?php print $filename; ?>" class="content"><?php print $row['name']; ?></a></td>
					<td>/workspace<?php print $file_cell_handle . $row['name']; ?></td>
					<td><?php print $file_size; ?> <?php print ($row['name'] != '.htaccess' && $row['name'] != 'workspace.conf' ? '<input name="items['.$abs_filename.']" type="checkbox" />' : ''); ?></td>
				</tr>	
			
			<?php
				        $bEven = !$bEven;
				    
				}
        else:
?>   
    <tr><td colspan="3">None Found</td></tr>
 
<?php  		
        endif;	
?>				

			</tbody>
		</table>


<?php					

	$pagination = array(
			'total_records' => $files_count_total,
			'total_pages' => max(ceil($files_count_total * (1 / $Admin->getConfigVar('pagination_maximum_rows', 'symphony'))), 1),
			'current_page' => $current_page,
			'viewing_start' => max(1, ($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony') + 1),
			'viewing_end' => min($current_page * $Admin->getConfigVar('pagination_maximum_rows', 'symphony'), $files_count_total),
			'next_page' => ($files_count_remaining > $Admin->getConfigVar('pagination_maximum_rows', 'symphony') ? ($current_page + 1) : NULL),
			'previous_page' => ($current_page > 1 ? ($current_page - 1) : NULL),
			'first_page' => ($current_page > 1 ? 1 : NULL),
			'last_page' => ($files_count_remaining > $Admin->getConfigVar('pagination_maximum_rows', 'symphony') ? max(ceil($files_count_total * (1 / $Admin->getConfigVar('pagination_maximum_rows', 'symphony'))), 1) : NULL)

		);

	extract($pagination);

	if($total_pages > 1):

	$path = str_replace('/workspace', '', $path);
	
	if(trim($path, '/') == '') $path = NULL;
	else $path = '&amp;filter='.$path;

?>					

		<ul class="page">
			<li>
<?php 

	if($first_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$path.'&amp;pg=1">First</a>' . CRLF;

	else
		print '				First';
?>			
			</li>

			<li>
<?php 

	if($previous_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$path.'&amp;pg='.$previous_page.'">&larr; Previous</a>' . CRLF;

	else
		print '				&larr; Previous';
?>			
			</li>
			<li title="Viewing <?php print $viewing_start; ?> - <?php print $viewing_end; ?> of <?php print $total_records; ?> file">Page <?php print $current_page; ?> of <?php print $total_pages; ?></li>

			<li>
<?php 

	if($next_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$path.'&amp;pg='.$next_page.'">Next &rarr;</a>' . CRLF;

	else
		print '				Next &rarr;';
?>			
			</li>			
			<li>
<?php 

	if($last_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$path.'&amp;pg='.$last_page.'">Last</a>' . CRLF;

	else
		print '				Last';
?>			
			</li>
		</ul>

<?php
	
	endif;
	
?>

		<div id="config">
			<h3>Upload File</h3>
			<fieldset id="upload">
				<input name="MAX_FILE_SIZE" type="hidden" value="<?php print $Admin->getConfigVar('max_upload_size', 'admin'); ?>" />
				<div><input type="file" name="file" /></div>
				<label>Destination Folder
					<select name="destination">
						<option value="workspace/">workspace/</option>
<?php

			foreach($directories as $d){
				$d_clean = str_replace('/workspace', '', $d);
				$d_clean = trim($d_clean, '/');	

				if(!in_array($d_clean, $ignore)){ 
?>						
                		<option value="<?php print ltrim($d, '/'); ?>"<?php print ($path == $d ? ' selected="selected"' : ""); ?>><?php print ltrim($d, '/'); ?></option>
<?php
				}
			}
?>	
					</select>
				</label>
				<input name="action[upload]" type="submit" value="Upload" />
			</fieldset>
		</div>

	</form>
