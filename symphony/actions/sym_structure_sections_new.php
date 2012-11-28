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

	if(@array_key_exists("save", $_POST['action']) || @array_key_exists("done", $_POST['action'])) {
	    $required = array('name');
	    $fields = $_POST['fields'];

	    for($i=0;$i<count($required);$i++) {
	        if(trim($fields[$required[$i]]) == "") {
	            $errors[$required[$i]] = true;
	        }
	    }

	    if(is_array($errors)){
	        define("__SYM_ENTRY_MISSINGFIELDS__", true);

	    }else{

	        $query  = 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_sections LIMIT 1';
	        $next = $DB->fetchVar("next", 0, $query);

	        $fields['sortorder'] = ($next ? $next : '1');
	        $fields['handle'] = Lang::createHandle($fields['name'], $Admin->getConfigVar('handle_length', 'admin'));
		    $fields['primary_field'] = 1;

			##Duplicate
			if($DB->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `handle` = '" . $fields['handle'] . "' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Section", "name"), false, 'error');

			}elseif(in_array($fields['handle'], array("authors", "navigation", "comments", "options"))){
				$Admin->pageAlert("reserved-section-name", NULL, false, 'error');

			}else{

				$fields['commenting'] = (isset($fields['commenting']) ? 'on' : 'off');
				$fields['author_column'] = (isset($fields['author_column']) ? 'show' : 'hide');
				$fields['date_column'] = (isset($fields['date_column']) ? 'show' : 'hide');
				$fields['calendar_show'] = (isset($fields['calendar_show']) ? 'show' : 'hide');
				#$fields['valid_xml_column'] = (isset($fields['valid_xml_column']) ? 'show' : 'hide');

		        if($DB->insert($fields, "tbl_sections")){

		            $section_id = $DB->getInsertID();

					$customfield = array();

					$customfield['name'] = 'Title';
					$customfield['handle'] = 'title';
					$customfield['parent_section'] = $section_id;
					$customfield['format'] = '1';
					$customfield['type'] = 'input';
					$customfield['description'] = NULL;
					$customfield['sortorder'] = max(1, $DB->fetchVar('next', 0, 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_customfields LIMIT 1'));
					$customfield['location'] = 'main';

					$DB->insert($customfield, "tbl_customfields");
				    $field_id = $DB->getInsertID();

					$DB->query("UPDATE `tbl_sections` SET `primary_field` = '$field_id' WHERE `id` = '$section_id' LIMIT 1");
					$DB->query("INSERT INTO `tbl_sections_visible_columns` VALUES ($field_id, $section_id)");

		            $Admin->updateMetadata("customfield", $field_id);
		            $Admin->updateMetadata("section", $section_id);

					$Admin->rebuildWorkspaceConfig();
					$Admin->flush_cache(array("entries", "comments"));

					###
					# Delegate: Create
					# Description: Creation of a new Section. Section ID and Primary Field ID are provided.
					$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('section_id' => $section_id,
														  				   	     'field_id' => $field_id));

	                if(@array_key_exists("save", $_POST['action']))
 						General::redirect(URL . "/symphony/?page=/structure/sections/edit/&id=" . $section_id . "&_f=saved");

	                General::redirect(URL . "/symphony/?page=/structure/sections/");

		        }
	        }
	    }
	}
?>