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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");
	
	$GLOBAL['cENTITIES'] = array(
		"named"       => array("&nbsp;","&iexcl;","&cent;","&pound;","&curren;","&yen;","&brvbar;","&sect;","&uml;","&copy;","&ordf;","&laquo;","&not;","&shy;","&reg;","&macr;","&deg;","&plusmn;","&sup2;","&sup3;","&acute;","&micro;","&para;","&middot;","&cedil;","&sup1;","&ordm;","&raquo;","&frac14;","&frac12;","&frac34;","&iquest;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&Ccedil;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ETH;","&Ntilde;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&times;","&Oslash;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&Yacute;","&THORN;","&szlig;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&igrave;","&iacute;","&icirc;","&iuml;","&eth;","&ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&divide;","&oslash;","&ugrave;","&uacute;","&ucirc;","&uuml;","&yacute;","&thorn;","&yuml;","&fnof;","&Alpha;","&Beta;","&Gamma;","&Delta;","&Epsilon;","&Zeta;","&Eta;","&Theta;","&Iota;","&Kappa;","&Lambda;","&Mu;","&Nu;","&Xi;","&Omicron;","&Pi;","&Rho;","&Sigma;","&Tau;","&Upsilon;","&Phi;","&Chi;","&Psi;","&Omega;","&alpha;","&beta;","&gamma;","&delta;","&epsilon;","&zeta;","&eta;","&theta;","&iota;","&kappa;","&lambda;","&mu;","&nu;","&xi;","&omicron;","&pi;","&rho;","&sigmaf;","&sigma;","&tau;","&upsilon;","&phi;","&chi;","&psi;","&omega;","&thetasym;","&upsih;","&piv;","&bull;","&hellip;","&prime;","&Prime;","&oline;","&frasl;","&weierp;","&image;","&real;","&trade;","&alefsym;","&larr;","&uarr;","&rarr;","&darr;","&harr;","&crarr;","&lArr;","&uArr;","&rArr;","&dArr;","&hArr;","&forall;","&part;","&exist;","&empty;","&nabla;","&isin;","&notin;","&ni;","&prod;","&sum;","&minus;","&lowast;","&radic;","&prop;","&infin;","&ang;","&and;","&or;","&cap;","&cup;","&int;","&there4;","&sim;","&cong;","&asymp;","&ne;","&equiv;","&le;","&ge;","&sub;","&sup;","&nsub;","&sube;","&supe;","&oplus;","&otimes;","&perp;","&sdot;","&lceil;","&rceil;","&lfloor;","&rfloor;","&lang;","&rang;","&loz;","&spades;","&clubs;","&hearts;","&diams;","&quot;","&amp;","&lt;","&gt;","&OElig;","&oelig;","&Scaron;","&scaron;","&Yuml;","&circ;","&tilde;","&ensp;","&emsp;","&thinsp;","&zwnj;","&zwj;","&lrm;","&rlm;","&ndash;","&mdash;","&lsquo;","&rsquo;","&sbquo;","&ldquo;","&rdquo;","&bdquo;","&dagger;","&Dagger;","&permil;","&lsaquo;","&rsaquo;","&euro;","&apos;"),
		"decimal"     => array("&#160;","&#161;","&#162;","&#163;","&#164;","&#165;","&#166;","&#167;","&#168;","&#169;","&#170;","&#171;","&#172;","&#173;","&#174;","&#175;","&#176;","&#177;","&#178;","&#179;","&#180;","&#181;","&#182;","&#183;","&#184;","&#185;","&#186;","&#187;","&#188;","&#189;","&#190;","&#191;","&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;","&#402;","&#913;","&#914;","&#915;","&#916;","&#917;","&#918;","&#919;","&#920;","&#921;","&#922;","&#923;","&#924;","&#925;","&#926;","&#927;","&#928;","&#929;","&#931;","&#932;","&#933;","&#934;","&#935;","&#936;","&#937;","&#945;","&#946;","&#947;","&#948;","&#949;","&#950;","&#951;","&#952;","&#953;","&#954;","&#955;","&#956;","&#957;","&#958;","&#959;","&#960;","&#961;","&#962;","&#963;","&#964;","&#965;","&#966;","&#967;","&#968;","&#969;","&#977;","&#978;","&#982;","&#8226;","&#8230;","&#8242;","&#8243;","&#8254;","&#8260;","&#8472;","&#8465;","&#8476;","&#8482;","&#8501;","&#8592;","&#8593;","&#8594;","&#8595;","&#8596;","&#8629;","&#8656;","&#8657;","&#8658;","&#8659;","&#8660;","&#8704;","&#8706;","&#8707;","&#8709;","&#8711;","&#8712;","&#8713;","&#8715;","&#8719;","&#8721;","&#8722;","&#8727;","&#8730;","&#8733;","&#8734;","&#8736;","&#8743;","&#8744;","&#8745;","&#8746;","&#8747;","&#8756;","&#8764;","&#8773;","&#8776;","&#8800;","&#8801;","&#8804;","&#8805;","&#8834;","&#8835;","&#8836;","&#8838;","&#8839;","&#8853;","&#8855;","&#8869;","&#8901;","&#8968;","&#8969;","&#8970;","&#8971;","&#9001;","&#9002;","&#9674;","&#9824;","&#9827;","&#9829;","&#9830;","&#34;","&#38;","&#60;","&#62;","&#338;","&#339;","&#352;","&#353;","&#376;","&#710;","&#732;","&#8194;","&#8195;","&#8201;","&#8204;","&#8205;","&#8206;","&#8207;","&#8211;","&#8212;","&#8216;","&#8217;","&#8218;","&#8220;","&#8221;","&#8222;","&#8224;","&#8225;","&#8240;","&#8249;","&#8250;","&#8364;","&#39;"),
		"hexadecimal" => array("&#xa0;","&#xa1;","&#xa2;","&#xa3;","&#xa4;","&#xa5;","&#xa6;","&#xa7;","&#xa8;","&#xa9;","&#xaa;","&#xab;","&#xac;","&#xad;","&#xae;","&#xaf;","&#xb0;","&#xb1;","&#xb2;","&#xb3;","&#xb4;","&#xb5;","&#xb6;","&#xb7;","&#xb8;","&#xb9;","&#xba;","&#xbb;","&#xbc;","&#xbd;","&#xbe;","&#xbf;","&#xc0;","&#xc1;","&#xc2;","&#xc3;","&#xc4;","&#xc5;","&#xc6;","&#xc7;","&#xc8;","&#xc9;","&#xca;","&#xcb;","&#xcc;","&#xcd;","&#xce;","&#xcf;","&#xd0;","&#xd1;","&#xd2;","&#xd3;","&#xd4;","&#xd5;","&#xd6;","&#xd7;","&#xd8;","&#xd9;","&#xda;","&#xdb;","&#xdc;","&#xdd;","&#xde;","&#xdf;","&#xe0;","&#xe1;","&#xe2;","&#xe3;","&#xe4;","&#xe5;","&#xe6;","&#xe7;","&#xe8;","&#xe9;","&#xea;","&#xeb;","&#xec;","&#xed;","&#xee;","&#xef;","&#xf0;","&#xf1;","&#xf2;","&#xf3;","&#xf4;","&#xf5;","&#xf6;","&#xf7;","&#xf8;","&#xf9;","&#xfa;","&#xfb;","&#xfc;","&#xfd;","&#xfe;","&#xff;","&#x192;","&#x391;","&#x392;","&#x393;","&#x394;","&#x395;","&#x396;","&#x397;","&#x398;","&#x399;","&#x39a;","&#x39b;","&#x39c;","&#x39d;","&#x39e;","&#x39f;","&#x3a0;","&#x3a1;","&#x3a3;","&#x3a4;","&#x3a5;","&#x3a6;","&#x3a7;","&#x3a8;","&#x3a9;","&#x3b1;","&#x3b2;","&#x3b3;","&#x3b4;","&#x3b5;","&#x3b6;","&#x3b7;","&#x3b8;","&#x3b9;","&#x3ba;","&#x3bb;","&#x3bc;","&#x3bd;","&#x3be;","&#x3bf;","&#x3c0;","&#x3c1;","&#x3c2;","&#x3c3;","&#x3c4;","&#x3c5;","&#x3c6;","&#x3c7;","&#x3c8;","&#x3c9;","&#x3d1;","&#x3d2;","&#x3d6;","&#x2022;","&#x2026;","&#x2032;","&#x2033;","&#x203e;","&#x2044;","&#x2118;","&#x2111;","&#x211c;","&#x2122;","&#x2135;","&#x2190;","&#x2191;","&#x2192;","&#x2193;","&#x2194;","&#x21b5;","&#x21d0;","&#x21d1;","&#x21d2;","&#x21d3;","&#x21d4;","&#x2200;","&#x2202;","&#x2203;","&#x2205;","&#x2207;","&#x2208;","&#x2209;","&#x220b;","&#x220f;","&#x2211;","&#x2212;","&#x2217;","&#x221a;","&#x221d;","&#x221e;","&#x2220;","&#x2227;","&#x2228;","&#x2229;","&#x222a;","&#x222b;","&#x2234;","&#x223c;","&#x2245;","&#x2248;","&#x2260;","&#x2261;","&#x2264;","&#x2265;","&#x2282;","&#x2283;","&#x2284;","&#x2286;","&#x2287;","&#x2295;","&#x2297;","&#x22a5;","&#x22c5;","&#x2308;","&#x2309;","&#x230a;","&#x230b;","&#x2329;","&#x232a;","&#x25ca;","&#x2660;","&#x2663;","&#x2665;","&#x2666;","&#x22;","&#x26;","&#x3c;","&#x3e;","&#x152;","&#x153;","&#x160;","&#x161;","&#x178;","&#x2c6;","&#x2dc;","&#x2002;","&#x2003;","&#x2009;","&#x200c;","&#x200d;","&#x200e;","&#x200f;","&#x2013;","&#x2014;","&#x2018;","&#x2019;","&#x201a;","&#x201c;","&#x201d;","&#x201e;","&#x2020;","&#x2021;","&#x2030;","&#x2039;","&#x203a;","&#x20ac;","&#x27;"),
		"unicode"	  => array("&#160;","¡","¢","£","¤","¥","¦","§","¨","©","ª","«","¬","­","®","¯","°","±","²","³","´","µ","¶","·","¸","¹","º","»","¼","½","¾","¿","À","Á","Â","Ã","Ä","Å","Æ","Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ð","Ñ","Ò","Ó","Ô","Õ","Ö","×","Ø","Ù","Ú","Û","Ü","Ý","Þ","ß","à","á","â","ã","ä","å","æ","ç","è","é","ê","ë","ì","í","î","ï","ð","ñ","ò","ó","ô","õ","ö","÷","ø","ù","ú","û","ü","ý","þ","ÿ","ƒ","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","•","…","","","","","","","","™","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","&quot;","&amp;","&lt;","&gt;","Œ","œ","Š","š","Ÿ","ˆ","˜","","","","","","","","","","‘","’","‚","“","”","„","†","‡","‰","‹","›","€","&#39;")
	);

	static $processErrors = array();
   
	function trapXMLError($errno, $errstr, $errfile, $errline, $errcontext, $ret=false){
		
		global $processErrors;
		
		if ($ret === true) return $processErrors;
		
		$tag = 'DOMDocument::';
		$processErrors[] = array("type" => "xml", "number" => $errno, "message" => str_replace($tag, '', $errstr), "file" => $errfile, "line" => $errline); //, "context" => $errcontext);
	}
	
	function trapXSLError($errno, $errstr, $errfile, $errline, $errcontext, $ret=false){
		
		global $processErrors;
		
		if ($ret === true) return $processErrors;
		
		$tag = 'DOMDocument::';
		$processErrors[] = array("type" => "xsl", "number" => $errno, "message" => str_replace($tag, '', $errstr), "file" => $errfile, "line" => $errline); //, "context" => $errcontext);
	}	
				
	if (PHP_VERSION >= 5 && _XSLT_AVAILABLE_) {
	   // Emulate the old xslt library functions since they dont exist in php5
						   
		function xslt_create() {
		   return new XsltProcessor();
		}
	
		function xslt_process($xsltproc,
		                     $xml_arg,
		                     $xsl_arg,
		                     $xslcontainer = null,
		                     $args = null,
		                     $params = null) {
		                         
			// Start with preparing the arguments
			$xml_arg = str_replace('arg:', '', $xml_arg);
			$xsl_arg = str_replace('arg:', '', $xsl_arg);
			
			// Create instances of the DomDocument class
			$xml = new DomDocument;
			$xsl = new DomDocument;	     
			 
			// Set up error handling					
			$ehOLD = ini_set('html_errors', false);		
				
			// Load the xml document
			set_error_handler('trapXMLError');	
			$xml->loadXML($args[$xml_arg]);
			
			// Must restore the error handler to avoid problems
			restore_error_handler();
			
			// Load the xml document
			set_error_handler('trapXSLError');	
			$xsl->loadXML($args[$xsl_arg]);

			// Load the xsl template
			$xsltproc->importStyleSheet($xsl);
			
			// Set parameters when defined
			if ($params) {
			   foreach ($params as $param => $value) {
			       $xsltproc->setParameter("", $param, $value);
			   }
			}
			
			restore_error_handler();
			
			// Start the transformation
			set_error_handler('trapXMLError');	
			$processed = $xsltproc->transformToXML($xml);

			// Restore error handling
			ini_set('html_errors', $ehOLD);
			restore_error_handler();	
				
			// Put the result in a file when specified
			if ($xslcontainer) {
			   return @file_put_contents($xslcontainer, $processed);
			   
			} else {
			   return $processed;
			}

		}
		
		function xslt_free($xsltproc) {
		   unset($xsltproc);
		}
	}
	
	Class XsltProcess{
	
		var $_xml;
		var $_xsl;
		
		var $_errors;
		
		function XsltProcess($xml=null, $xsl=null){
			
			if(!_XSLT_AVAILABLE_) return false;
			
			$this->_xml = $xml;
			$this->_xsl = $xsl;
			
			$this->_errors = array();
			
			return true;
			
		}
		
		function process($xml=null, $xsl=null, $param=array()){
			
			if($xml) $this->_xml = $xml;
			if($xsl) $this->_xsl = $xsl;
			
			$xml = trim($xml);
			$xsl = trim($xsl);
			
			if(!is_array($param)) $param = array();
			
			if(!_XSLT_AVAILABLE_) return false; //dont let process continue if no xsl functionality exists
			
			//DOMXML Extension	
			if(_USING_DOMXML_XSLT_){

				// Set up error handling					
				$ehOLD = ini_set('html_errors', false);						
				set_error_handler('trapXSLError');
				
				$xmldoc = domxml_open_mem($this->_xml, DOMXML_LOAD_PARSING, $xmlErrors);				
				$xsldoc = domxml_xslt_stylesheet($this->_xsl);
				
				if(is_object($xmldoc) && is_object($xsldoc)){
					$result = $xsldoc->process($xmldoc, $param);
					$result = $xsldoc->result_dump_mem($result);
				}
				
				// Restore error handling
				ini_set('html_errors', $ehOLD);
				restore_error_handler();
					
				//Process the errors while opening the XML
				while($e = @array_shift($xmlErrors))
					$this->__error("2", $e['errormessage'], "xml", $e['line']);
							
				//Use one of the custom error handlers to grab a list of processing errors
				$errors = trapXMLError(null, null, null, null, null, true);	
				
				//Process the rest of the errors
				while($error = @array_shift($errors))
					$this->__error($error['number'], $error['message'], $error['type'], $error['line']);	
																
				unset($xmldoc); unset($xsldoc);
				
			//PHP4/5 XSL Extension
			}else{
			
				$arguments = array(
			   		'/_xml' => $this->_xml,
			   		'/_xsl' => $this->_xsl
				);
				
				$xsltproc = xslt_create();
				
				##Make sure a bad document() call doesnt break the site
				if(PHP_VERSION < 5) xslt_setopt($xsltproc, XSLT_SABOPT_IGNORE_DOC_NOT_FOUND);
					
				$result = @xslt_process(
				   $xsltproc,
				   'arg:/_xml',
				   'arg:/_xsl',
				   null,
				   $arguments,
				   $param
				);	
									
				if(PHP_VERSION >= 5){
					
					//Use one of the custom error handlers to grab a list of processing errors
					$errors = trapXMLError(null, null, null, null, null, true);	
					
					while($error = @array_shift($errors))
						$this->__error($error['number'], $error['message'], $error['type'], $error['line']);
														
				}else{							
					if (!$result && xslt_errno($xsltproc) > 0) {					
						$this->__error(xslt_errno($xsltproc), xslt_error($xsltproc));							
					}
				}
				
				xslt_free($xsltproc);
				
			}
			
			return $result;		
		}
		
		function __error($number, $message, $type=NULL, $line=NULL){
			
			$context = NULL;
			
			if($type == "xml") $context = $this->_xml;
			if($type == "xsl") $context = $this->_xsl;
			
			$this->_errors[] = array(
									"number" => $number, 
									"message" => $message, 
									"type" => $type, 
									"line" => $line,
									"context" => $context);		
		}
		
		function isErrors(){		
			return (!empty($this->_errors) ? true : false);				
		}
		
		function getError($all=false){		
			return ($all ? $this->_errors : array_shift($this->_errors));				
		}
		
	}

?>