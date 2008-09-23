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

	if(array_key_exists("begin", $_POST['action'])){
		
		$start = time();
		$Admin->log->writeToLog("============================================\r\n", true);
		$Admin->log->pushToLog("Beginning 1.1.00 Migration", SYM_LOG_NOTICE, true, true); 	
		
		##Create Backup
		$Admin->log->pushToLog("Backing Up Existing Database...", SYM_LOG_NOTICE, true, false);
		require_once(TOOLKIT . "/class.mysqldump.php");
		
		$data  = "\nTRUNCATE `tbl_entries`;";
		$data .= "\nTRUNCATE `tbl_entries2customfields`;";
		$data .= "\nTRUNCATE `tbl_comments`;";
		$data .= "\nTRUNCATE `tbl_entries_types`;";
		$data .= "\nDELETE FROM `tbl_metadata` WHERE `class` IN('entry', 'comment');";	
		
		$dump = new MySQLDump($DB);
						
		$data .= $dump->takeDump($Admin->getConfigVar("tbl_prefix", "database") . "entries", "DATA_ONLY");
		$data .= $dump->takeDump($Admin->getConfigVar("tbl_prefix", "database") . "comments", "DATA_ONLY");
		$data .= $dump->takeDump($Admin->getConfigVar("tbl_prefix", "database") . "metadata", "DATA_ONLY", "`class` IN('entry','comment')");
				
		unset($dump);
		
		if(!@file_put_contents(TMP . "/migration-backup.sql", $data)){
			define("__SYM_MIGRATION_ERRORS__", true);	
			$Admin->log->pushToLog("Failed.", SYM_LOG_NOTICE, true, true, true); 
			
		}else		
			$Admin->log->pushToLog("Done.", SYM_LOG_NOTICE, true, true, true); 

		##Entries
		if(!defined("__SYM_MIGRATION_ERRORS__")){
			$Admin->log->pushToLog("Migrating Entries Table", SYM_LOG_NOTICE, true, true); 	
			
			//---------------------			
				
				$TFM = new TextformatterManager(array('parent' => &$Admin));
						
				$sql = "SELECT `id`, `title`, `body_raw`, `formatter` FROM `tbl_entries`";
				
				if(!$entries = $DB->fetch($sql)){
					$error = true;			
					$Admin->log->pushToLog("Could not get entry records from database", SYM_LOG_ERROR, true, true); 	
				}
				
				if(!$error && is_array($entries) && !empty($entries)){
					foreach($entries as $e){
						
						$error = false;
						
						$Admin->log->pushToLog("Converting '".$e['title']."' ... ", SYM_LOG_NOTICE, true, false);
						
						$fields = array();		
						
						if($e['formatter'] && !$formatter = $TFM->create($e['formatter'], array(), true)){
							$error = true;
							$Admin->log->pushToLog("Failed (Could not create formatter '".$e['formatter']."')", SYM_LOG_NOTICE, true, true, true); 
						
						}else{	
							if($e['formatter'] != NULL){
								$fields['body']	= $formatter->run(General::reverse_sanitize($e['body_raw']));
								
							}else
								$fields['body'] = General::reverse_sanitize($e['body_raw']);						
							
							$fields['excerpt']	= General::limitWords($fields['body']);	
							$fields['excerpt'] 	= preg_replace('/[\s]+/', ' ', $fields['excerpt']);				
							
							##Update the entry
							if(!$DB->update($fields, "tbl_entries", "WHERE `id` = '".$e['id']."' LIMIT 1")){
								$error = true;
								$Admin->log->pushToLog("Failed (Problem updating fields)", SYM_LOG_NOTICE, true, true, true); 
							}else $Admin->updateMetadata("entry", $e['id'], false);
							
							##Update the custom fields			
							$sql = "SELECT * FROM `tbl_entries2customfields` WHERE `entry_id` = '".$e['id']."'";
							
							$customfields = $DB->fetch($sql);		
										
							if(!$error && is_array($customfields) && !empty($customfields)){
								foreach($customfields as $c){	
									
									$fields = array();
									
									$format = $DB->fetchVar("format", 0, "SELECT `format` FROM `tbl_customfields` WHERE `id` = '".$c['field_id']."' LIMIT 1");
			
									if($format == '1' && $e['formatter'] && !@is_object($formatter)){
										$error = true;
										$Admin->log->pushToLog("Failed (Could not create formatter '".$e['formatter']."')", SYM_LOG_NOTICE, true, true, true); 
									
									}else{	
										
										if(!@is_object($formatter)){
											$fields['value'] = $formatter->run(General::reverse_sanitize($c['value_raw']));
			
										}else
											$fields['value'] = General::reverse_sanitize($c['value_raw']);
											
									}	

									##Update the entry
									if(!$DB->update($fields, "tbl_entries2customfields", "WHERE `field_id` = '".$c['field_id']."' AND `entry_id` = '".$e['id']."' LIMIT 1")){
										$Admin->log->pushToLog("Failed (Problem updating custom fields)", SYM_LOG_NOTICE, true, true, true); 
										$error = true;
									}
									
								}	
							}								
						}
						
						if(!$error)							
							$Admin->log->pushToLog("Done.", SYM_LOG_NOTICE, true, true, true);
								
					}	
				}
			
			//---------------------
										
			if($error){
				define("__SYM_MIGRATION_RESTORE_ERRORS__", true);	
				$Admin->log->pushToLog("Migrating Entries Failed.", SYM_LOG_ERROR, true, true); 			
				
			}else
				$Admin->log->pushToLog("Migrating Entries Complete.", SYM_LOG_NOTICE, true, true); 
		}		
		
		##Flush the cache
		$Admin->log->pushToLog("Flushing front-end cache...Done", SYM_LOG_NOTICE, true, true); 	
		$Admin->flush_cache(array("entries", "authors", "comments"));
		
		##Force Workspace Config Update
		if(!defined("__SYM_MIGRATION_ERRORS__")){
			$Admin->log->pushToLog("Forcing Workspace Configuration Refresh...", SYM_LOG_NOTICE, true, false); 								
			if(!$Admin->rebuildWorkspaceConfig()){
				define("__SYM_MIGRATION_RESTORE_ERRORS__", true);	
				$Admin->log->pushToLog("Failed.", SYM_LOG_NOTICE, true, true, true); 				
			}else
				$Admin->log->pushToLog("Done.", SYM_LOG_NOTICE, true, true, true); 
		}		
		
		if(!defined("__SYM_MIGRATION_ERRORS__"))
			$Admin->log->pushToLog("Migration Completed", SYM_LOG_NOTICE, true, true); 
			
		else
			$Admin->log->pushToLog("Migration Failed", SYM_LOG_ERROR, true, true); 
			
		$Admin->log->writeToLog("============================================\r\n", true);	
		
	}	
	
	if(array_key_exists("restore", $_POST['action'])){
		
		$start = time();
		$Admin->log->writeToLog("============================================\r\n", true);
		$Admin->log->pushToLog("Reverting Migration", SYM_LOG_NOTICE, true, true); 	
		
		##Read the backup file
		$Admin->log->pushToLog("Reading Migration Backup SQL...", SYM_LOG_NOTICE, true, false); 								
		if(!$queries = file_get_contents(TMP . "/migration-backup.sql")){						
			define("__SYM_MIGRATION_RESTORE_ERRORS__", true);	
			$Admin->log->pushToLog("Failed.", SYM_LOG_NOTICE, true, true, true); 
			
		}else
			$Admin->log->pushToLog("Done.", SYM_LOG_NOTICE, true, true, true); 
			
		
		##Import the backup			
		if(!defined("__SYM_MIGRATION_RESTORE_ERRORS__")){
			$Admin->log->pushToLog("Updating Tables...", SYM_LOG_NOTICE, true, false); 								
			if(!$DB->import($queries)){						
				define("__SYM_MIGRATION_RESTORE_ERRORS__", true);	
				$Admin->log->pushToLog("Failed.", SYM_LOG_NOTICE, true, true, true); 
				
			}else
				$Admin->log->pushToLog("Done.", SYM_LOG_NOTICE, true, true, true); 
		}
		
		##Flush the cache
		$Admin->log->pushToLog("Flushing front-end cache...Done", SYM_LOG_NOTICE, true, true); 	
		$Admin->flush_cache(array("entries", "authors", "comments"));		
		
		if(!defined("__SYM_MIGRATION_RESTORE_ERRORS__"))
			$Admin->log->pushToLog("Migration Revert Completed", SYM_LOG_NOTICE, true, true); 
			
		else
			$Admin->log->pushToLog("Migration Revert Failed", SYM_LOG_ERROR, true, true); 
			
		$Admin->log->writeToLog("============================================\r\n", true);			
   }
   
?>