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

	print "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
	<title>Symphony | <!-- PAGE TITLE --></title>

<!-- HEAD EXTRAS -->
</head>

<body>
	
	<!-- ERRORS -->
	<h1><img src="assets/images/logo.png" alt="Symphony" /> <a<?php print ($Admin->getConfigVar("status", "public") == "offline" ? ' class="maintenance"' : ""); ?> href="<?php print URL; ?>/"><?php print stripslashes($Admin->getConfigVar("sitename", "general")); ?></a></h1>

	<ul id="navigation">
<?php

	$nav = $Admin->getNavArray();
	$page = $_GET['page'];

	if(empty($page)) $page = "/publish/entries/";
	
	$page_bits = explode("/", $page, 3);
	$section = $page_bits[1];
	
	$user_access_level = "author";

	if($Admin->authorIsOwner()) $user_access_level = "owner";
	elseif($Admin->authorIsSuper()) $user_access_level = "super";
		
	foreach($nav as $n){
		
		$n_bits = explode("/", $n['link'], 3);

		$current = ($section == $n_bits[1] ? true : false); 

		$can_access = false;
		
		if($n['visible'] != 'no'){
			
			if(!isset($n['limit']))
				$can_access = true;
				
			elseif($n['limit'] == "super" && ($user_access_level == "super" || $user_access_level == "owner"))
				$can_access = true;		
				
			elseif($n['limit'] == "owner" && $user_access_level == "owner")
				$can_access = true;									
			
			if($can_access) {
				
				print "\t\t\t<li".($current ? " class=\"here\"" : "").">". $n["name"] ."\n";	
				print "\t\t\t\t<ul>\n";
				 
				foreach($n["children"] as $c){
					
					$can_access_child = false;	
								
					if($c['visible'] != 'no'){
					
						if(!isset($c['limit']))
							$can_access_child = true;
							
						elseif($c['limit'] == "super" && ($user_access_level == "super" || $user_access_level == "owner"))
							$can_access_child = true;		
							
						elseif($c['limit'] == "owner" && $user_access_level == "owner")
							$can_access_child = true;							
						
						if($can_access_child) {
							if(!empty($c['children']) && is_array($c['children'])){
								
								print "\t\t\t\t\t<li><h2>". $c["name"] ."</h2>\n";
								
								print "\t\t\t\t\t\t<ul>\n";
								
									foreach($c["children"] as $cc)
										print "\t\t\t\t\t\t\t<li><a href=\"?page=". $cc["link"] ."\">". $cc["name"] ."</a></li>\n";
		
								print "\t\t\t\t\t\t</ul>\n";
								print "\t\t\t\t\t</li>\n";
								
							}else{
								print "\t\t\t\t\t<li><a href=\"?page=". $c["link"] ."\">". $c["name"] ."</a> ". (isset($c["new_link"]) ? "<a href=\"?page=". $c["new_link"] ."\"><img alt=\"new\" src=\"\" title=\"".$c["new_name"]."\" /></a>" : "") ." </li>\n";
							}
						}
					}
					
				}	
				
				print "\t\t\t\t</ul>\n\t\t\t</li>\n"; 
			}
		}
	}

?>
	</ul>
	
	<!-- Content Begins -->

<!-- CONTENT -->

	<!-- content ends -->

	<ul id="user">
		<li><a href="?page=/settings/authors/edit/&amp;id=<?php print $Admin->getAuthorID(); ?>"><?php print $Admin->getAuthorName(); ?></a></li>
		<li><a href="?page=/logout/">Logout</a></li>
	</ul>
</body>
</html>