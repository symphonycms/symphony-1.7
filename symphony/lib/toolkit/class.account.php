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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

    Class Account extends Gateway{

        var $_parent;

        function Account(&$parent){

            $this->_parent =& $parent;

            $this->setopt("URL", rtrim($this->_parent->getConfigVar("acct_server", "symphony"), '/') . '/');
            $this->setopt("POST", 1);

        }

        function __verify($data){

            if(strpos($data, "<error>") !== false){ //<-- Crude, but effective

                $xml = new XmlDoc();
                $xml->parseString($data);
                $result = $xml->getArray();

                $server_message = $result['result'][0]['error'][0];

                $this->_parent->fatalError("Support Server Message", "There was a problem processing your request. The following message was returned from the server</p><dl><dt>Server Error</dt><dd>$server_message<dd></dl>", true, true);

            }

            return $data;
        }

        ##Abstract the curler function so we can add some better error handling
        function __grab($action, $redirect_on_error = true){

			$domain = str_replace("http://", '', URL);

            $mandatory_fields = array("_a" => strtoupper($action),
                                      "_b" => $this->_parent->getConfigVar("build", "symphony"),
									  "_DOMAIN" => $domain
                                );

            foreach($mandatory_fields as $key => $val)
                $d[] = $key . "=" . urlencode($val);

            $mandatory_fields = implode("&", $d);

            if(trim($this->_postfields) != "")
                $mandatory_fields .= "&" . ltrim($this->_postfields, '&');

            $this->setopt("POSTFIELDS", $mandatory_fields);

            $data = $this->exec();

            $this->flush();

            if($data === false && $redirect_on_error)
    			General::redirect(URL."/symphony/?page=/system/message/");

            return $data;
        }

        function __processServerData($data, $return_array=true, $is_gzip=true, $verify=true){

            if(empty($data))
                return false;

            if($verify) $this->__verify($data);

            if($return_array){
                $xml = new XmlDoc();
                $xml->parseString($data);

                return($xml->getArray());
            }

            return true;

        }

		function checkCampfireServiceCompatiblity($handle, $owner, $version){
			$guid = md5($owner.$handle);
			$this->setopt("POSTFIELDS", array('guid' => $guid, 'version' => $version));
			$data = $this->__grab('SERVICE-CHECK-FOR-COMPATIBILITY');

            if(!$data = $this->__processServerData($data, true, true, false))
                return NULL;

			$data = $data['result'][0]['compatibility'];

			$result = $data['attributes'];
			$result['status'] = $data[0];

			return $result;
		}

        function updateCheck(){

            $data = $this->__grab("CHECKFORUPDATE");

            if(!$data = $this->__processServerData($data, true, true, false))
                return false;

            $update = array();

            if(!is_array($data['result'][0]['update']) || empty($data['result'][0]['update'])){
                $this->_parent->setConfigVar("lastupdatecheck", time(), "symphony");
                $this->_parent->saveConfig();
                return false;
            }

            $update = @array_merge($data['result'][0]["update"]["attributes"], array("details" => $data['result'][0]["update"][0]));

            return $update;


        }


    }

?>