<?php
	/**
	*Implements the methods for a WCTP transient client to communicate with a carrier gateway.
	*
	*@see <a href="http://www.wctp.org/release/wctp-v1r3_update1.pdf">www.wctp.org</a>
	*@author Patrick Labbett <patrick.labbett@gmail.com>
	*/

	class WCTP_Transient_Client{	
		
		/**
		*Array to hold carrier data
		*/
		public $carrier = '';	

		/**
		*Class constructer
		*
		*@param url	URL for the WCTP carrier gateway endpoint (required)
		*/
		public function __constructer($url = false){
			if(!$url){
				die('Missing required parameter $url');
			}
			else{
				$this->carrier['url'] = $url;
			}
		}
		
		
		public function SubmitClientMessage(){
		}

		public function ClientQuery(){
		}

		/**
		*Allows clients to request information about the DTDs that a server applciation currently supports.
		*
		*@param inquirer	Client making the VersionQuery request	(required)
		*@param dateTime	Time the request was made. Used to track responses from the carrier. (optional)
		*/
		public function VersionQuery($inquirer = false, $dateTime = false){
			if(!$inquirer){	die('Missing required parameter $inquirer.'); }
			
			$xml = 
				"<!DOCTYPE wctp-Operation SYSTEM \"http://dtd.wctp.org/wctp-dtd-v1r1.dtd\">
				<?xml version=\"1.0\" encoding=\"utf-8\" ?>
				<wctp-Operation>
				<wctp-VersionQuery inquirer=\"$inquirer\"";
				if(!$datetime){
					$xml .= " />";
				}
				else{
					$xml .= " dateTime=\"" . $this->format_datetime($dateTime) . "\" />";
				}
				$xml .= "</wctp-Operation>";

				$response = $this->http_post_xml($xml);
				$xml_object = simplexml_load_string($response);
				$this->carrier['response'] = '';
			 	$this->parse_xml($xml_object);
			
		}

		/**
		*Utility function to make the HTTP post request to the carrier's endpoint
		*
		*@param xml	Properly formatted WCTP XML request
		*@return 	xml response from carrier		
		*/
		private function http_post_xml($xml){
			$ch = curl_init();
		        	curl_setopt($ch, CURLOPT_URL, $this->carrier['url']);
		        	curl_setopt($ch, CURLOPT_POST, true);
		        	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    	$response = curl_exec($ch);
		    	curl_close($ch);
		    	return $response;
		}

		/**
		*Utility function to format date/time for WCTP date time format
		*
		*@param datetime Any PHP date format/description that can be used with strtotime()
		*@return WCTP formatted date/time
		*/
		private function format_datetime($datetime){
			return gmdate('Y-m-d\TH:i:s', $datetime);
		}

		/**
		*Recursive function to parse the XML response from the carrier gateway into an array
		*
		*@param xml xml response from carrier gateway
		*/
       		 private function parse_xml($xml){
		    
			foreach($xml->attributes() as $key => $value){
		    		$this->carrier['response'][$key] = $value;
			}
		    
			foreach($xml->children() as $key){
		    		$this->parse_xml($key);    
			}    
		}
	}

	$tc = new WCTP_Transient_Client('https://wctp.att.net/wctp'); //Initialize our Transient Client for use with AT&T
	$tc->VersionQuery('6143704609', mktime());
	print_r($tc->carrier['response']);

?>
