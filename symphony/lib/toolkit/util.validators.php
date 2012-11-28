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

	## This is an array of validation rules used by Custom Fields.
	## To add your own, simply create a new array element with the format
	## of $validators[X] = array('TITLE', 'RULE'), X is the new ID and must be unique
	## TITLE is the text that will appear in the "Validation" select box on Custom Fields
	## pages. RULE must be a complete, valid regular expressions. It can be either a string
	## or an array of strings to match against, such as different date formats etc..

	## The array ID of each rule is important since they are referenced
	## in the database by their ID. Changing the ID numbers will cause custom fields
	## to potentially reference other rules in the array.

	## Be aware that this file may be updated, losing any changes you have made. Always
	## make backups. If you would like a rule offically included in a distribution
	## of Symphony, please contact the development team (team@symphony21.com)

	$validators = array();

	$validators[0] = array('None', '');

	$validators[1] = array('Numeric', '/^[\d]+$/i');

	$validators[2] = array('Word Character', '/^[a-z]+$/i');

	$validators[3] = array('Alphanumeric', '/^[\w\d]+$/i');

	$validators[4] = array('Email', "/^[\d\w\/+!=#|$?%{^&}*`'~-][\d\w\/\.+!=#|$?%{^&}*`'~-]*@[A-Z0-9]([A-Z0-9.-]{1,61})?[A-Z0-9]\.[A-Z]{2,6}$/i");

	$validators[5] = array('URL', "/^([a-z]{3,}:\/\/)?([\w\d]+)?(\.[\w\d\/_-]+)+$/i");

	$validators[6] = array('Date (yyyy-mm-dd)',  '/^([123456789][[:digit:]]{3})-(0[1-9]|1[012])-(0[1-9]|[12][[:digit:]]|3[01])$/i');

	$validators[7] = array('Time (hh:mm:ss)', '/^(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/i');

	$validators[8] = array('Date and Time (yyyy-mm-dd hh:mm:ss)', '/^([123456789][[:digit:]]{3})-(0[1-9]|1[012])-(0[1-9]|[12][[:digit:]]|3[01]) (0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/');


?>