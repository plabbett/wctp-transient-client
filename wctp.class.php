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
		public function __construct($url = false){
			if(!$url){ die('Missing required parameter $url'); }
			else{
				$this->carrier['url'] = $url;
			}
		}
		
		/**
		*Initiate a message from a transient client to a carrier gateway
		*
		*@param senderID Phone number of sending device (required)
		*@param recipientID Phone number of device message was sent to (required)
		*@param message Alphanumeric message to send (required)
		*@param miscInfo Misc info field, some carriers use this for a passcode field (optional)
		*@param authorizationCode Required by some carriers
		*@param msgControl Array of message control options from WCTP specification
		*	sendResponsesToID         CDATA  #IMPLIED 
		*	allowResponse             ( true | false ) "true" 
		*	notifyWhenQueued          ( true | false ) "false" 
		*	notifyWhenDelivered       ( true | false ) "false" 
		*	notifyWhenRead            ( true | false ) "false" 
		*	deliveryPriority          ( HIGH | NORMAL | LOW) "NORMAL" 
		*	deliveryAfter             CDATA  #IMPLIED 
		*	deliveryBefore            CDATA  #IMPLIED 
		*	preformatted              ( true | false ) "false" 
		*	allowTruncation           ( true | false ) "true"
		*/
		public function SubmitClientMessage($senderID = false, $recipientID = false, $message = false, $miscInfo = false, $authorizationCode = false, $msgControl = false){
			if(!$senderID){	die('Missing required parameter $senderID.'); }
			if(!$recipientID){	die('Missing required parameter $recipientID.'); }
			if(!$message){	die('Missing required parameter $message.'); }
  
			$xml = '<!DOCTYPE wctp-Operation SYSTEM "http://dtd.wctp.org/wctp-dtd-v1r1.dtd">';
			$xml .= '<?xml version="1.0" encoding="utf-8" ?>';
			$xml .= '<wctp-Operation>';
			$xml .= '<wctp-SubmitClientMessage>';
			$xml .= '<wctp-SubmitClientHeader>';
			$xml .= '<wctp-ClientOriginator senderID="' . $senderID;
			if(!$miscInfo){
			}
			else{
				$xml .= '" miscInfo="' . $miscInfo . '" />';
			}

			if(!$msgControl){
			}
			else{
				foreach($msgControl as $option => $value){
					$messageControl .=  $option . '="' . $value . '" ';
				}
			
				$xml .= '<wctp-MessageControl ' . $messageControl . ' />';
			}
			$xml .= '<wctp-Recipient recipientID="' . $recipientID;
			if(!authorizationCode){
			}
			else{
				$xml .= '" authorizationCode="' . $authorizationCode . '" />';
			}
			$xml .= '</wctp-SubmitClientHeader>';
			$xml .= '<wctp-Payload>';
			$xml .= '<wctp-Alphanumeric>';
			$xml .= $message;
			$xml .= '</wctp-Alphanumeric>';
			$xml .= '</wctp-Payload>';
			$xml .= '</wctp-SubmitClientMessage>';
			$xml .= '</wctp-Operation>';
            
            
			$response = $this->http_post_xml($xml);
			$xml_object = simplexml_load_string($response);
			$this->carrier['response'] = '';
		 	$this->parse_xml($xml_object);
		}

		/**
		*Poll operation to check for status information or responses to a message submitted by SubmitClientMessage
		*
		*@param senderID Phone number of sending device (required)
		*@param recipientID Phone number of device message was sent to (required)
		*@param trackingNumber Tracking number recieved from response of successful SubmitClientMessage (required)
		*/
		public function ClientQuery($senderID = false, $recipientID = false, $trackingNumber = false){
			if(!$senderID){	die('Missing required parameter $senderID.'); }
			if(!$recipientID){	die('Missing required parameter $recipientID.'); }
			if(!$trackingNumber){	die('Missing required parameter $trackingNumber.'); }
			
			$xml = '<!DOCTYPE wctp-Operation SYSTEM "http://dtd.wctp.org/wctp-dtd-v1r1.dtd">';
			$xml .= '<?xml version="1.0" encoding="utf-8" ?>';
			$xml .= '<wctp-Operation>';
			$xml .= '<wctp-ClientQuery senderID="' . $senderID . 
				'" recipientID="' . $recipientID . 
				'" trackingNumber="' . $trackingNumber . '" />';
			$xml .= '</wctp-Operation>';
            
			$response = $this->http_post_xml($xml);
			$xml_object = simplexml_load_string($response);
			$this->carrier['response'] = '';
		 	$this->parse_xml($xml_object);
		}

		/**
		*Allows clients to request information about the DTDs that a server applciation currently supports.
		*
		*@param inquirer	Client making the VersionQuery request	(required)
		*@param dateTime	Time the request was made. Used to track responses from the carrier. (optional)
		*/
		public function VersionQuery($inquirer = false, $dateTime = false){
			if(!$inquirer){	die('Missing required parameter $inquirer.'); }
			
			$xml = "<!DOCTYPE wctp-Operation SYSTEM \"http://dtd.wctp.org/wctp-dtd-v1r1.dtd\">";
			$xml .= "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
			$xml .= "<wctp-Operation>";
			$xml .= "<wctp-VersionQuery inquirer=\"$inquirer\"";
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
			if(!$response){	die('Error: ' . curl_error($ch)); }
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

	//$tc = new WCTP_Transient_Client('https://wctp.att.net/wctp'); //Initialize our Transient Client for use with AT&T
	//$tc->VersionQuery('6145551234', mktime());
	//print_r($tc->carrier['response']);

?>
