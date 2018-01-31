<?
    class ViessControl extends IPSModule {
 
	const COMPORT_OPEN           = 'Open';             // Comport was just opened
	const COMPORT_INIT           = 'Init';             // Viessmann INIT was requested
	const COMPORT_READY          = 'Ready';            // Viessmann confirmed INIT; Control is READY to take commands
	const COMPORT_CLOSED         = 'Closed';           // Comport is closed
	const COMPORT_DATA_REQUESTED = 'DataRequested';    // Data was requested from the Control
	    
        public function Create() {
          /* Create is called ONCE on Instance creation and start of IP-Symcon.
             Status-Variables und Modul-Properties for permanent usage should be created here  */
          parent::Create(); 
        }
 
        public function ApplyChanges() {
          /* Called on 'apply changes' in the configuration UI and after creation of the instance */
          parent::ApplyChanges();
		
          $this->SetReceiveDataFilter(".*018EF6B5-AB94-40C6-AA53-46943E824ACF.*");
        }
 
        //=== Module Functions =========================================================================================
        public function ReceiveData($JSONString) {
	
	  $this->sendDebug( "Viess", "ReceiveData Begin", 0 );	
	  $this->sendDebug( "Viess", "  Mode: ".$this->GetBuffer( "PortState" ), 0 );
		
          // Receive data from serial port I/O
          $data = json_decode($JSONString);	
		
          // Process data
	  switch ( $this->GetBuffer( "PortState" ) )
	  {
	    case ViessControl::COMPORT_INIT:
	      // 0x06 confirms the 0x16 0x00 0x00 request 
	      if ( $data->Buffer == "\x06" )
		$this->SetBuffer( "PortState", ViessControl::COMPORT_READY );    
	      break;
			  
	    case ViessControl::COMPORT_DATA_REQUESTED:
	      $this->sendDebug( "Viess", "  Case B", 0 );
	      // data was requested from the control
	      // expected answer is like 0x06 41 07 01 01 55 25 02 07 01 8D
	      $receivedData = $this->GetBuffer( "ReceiveBuffer" );     // Get previously received data
	      $receivedData = $receivedData.$data->Buffer;             // Append newly received data
	      $this->SetBuffer( "ReceiveBuffer", $receivedData );      // Store fully received data to buffer
			  
	      $this->sendDebug( "Viess", "Received so far: ".$receivedData,0 );
			  
	      // Check, if answer to data request is complete
	      if ( strlen( $receivedData ) >= 3 ) // 0x06 is the simple ACK flag, 2nd byte needed
	      {	      
		 $this->sendDebug( "Viess", "Check all data is there... ", 0 );
		 // in the 3nd byte the length of the payload (ACK + 0x41 package start first) is defined
		 $expectedPayloadLength = ord($receivedData[2])+ 4; // Start 06 41 + length + Checksum
		 $this->sendDebug( "Viess", "Expected Payload length: ".$expectedPayloadLength, 0 );     
		      
		 if ( strlen( $receivedData ) >= $expectedPayloadLength )
		 {
		   $this->sendDebug( "Viess", "YES! ", 0 );
		   // Get Payload from transmitted data
	           $this->sendDebug( "Viess", "ReceiveData Length: ".ord($receivedData[7]), 0 );
		   $this->SetBuffer( "RequestedData", substr($receivedData, 8, ord($receivedData[7])));
	           $this->SetBuffer( "PortState", ViessControl::COMPORT_READY );  // Communication done 
		 }
	      }
	      break;
	  }
          $this->sendDebug( "Viess", "ReceiveData End", 0 );
          return true;
        }
        
        //=== Private Functions for Communication handling with Vitotronic ==============================================
        private function startCommunication() {
          // check serial port (parent)
          $SerialPortInstanceID = IPS_GetInstance($this->InstanceID)['ConnectionID']; 
          if ( $SerialPortInstanceID == 0 ) return false; // No parent assigned  
            
          $ModuleID = IPS_GetInstance($SerialPortInstanceID)['ModuleInfo']['ModuleID'];      
          if ( $ModuleID !== '{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}' ) return false; // wrong parent type
            
          // open serial port
          if ( IPS_GetProperty( $SerialPortInstanceID, "Open" ) != true )
          {
	        IPS_SetProperty( $SerialPortInstanceID, "Open", true );
	        IPS_ApplyChanges( $SerialPortInstanceID );
          }
		
	  if ( IPS_GetProperty( $SerialPortInstanceID, "Open" ) != true ) return false; // Port not open
		
          $this->SetBuffer( "PortState", ViessControl::COMPORT_OPEN );
            
          // send 0x04 to bring communication into a defined state (Protocol 300)
	  $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", 
						    "Buffer" => utf8_encode("\x04") )));
          $this->SetBuffer( "PortState", ViessControl::COMPORT_INIT );
	  sleep(1); // wait so vitotronic reacts	
		
          // now send 0x16 0x00 0x00 till Vitotronic has answered with 0x06 (in receive data) (Protocol 300)
	  $tryCounter = 10;
	  do {
	    $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", 
						      "Buffer" => utf8_encode("\x16\x00\x00") )));
            sleep(1); // wait 1 second
	    $tryCounter--;	  
	  } while ( $this->GetBuffer( "PortState" ) != ViessControl::COMPORT_READY AND $tryCounter > 0 );
		
          // Fehlerhandling / nicht unendlich laufen
	  if ( $tryCounter == 0 ) { return false; } else { return true; }
        } 
        
        private function endCommunication() {
	  // get serial port (parent) and check
	  $SerialPortInstanceID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
	  if ( $SerialPortInstanceID == 0 ) return false; // No parent assigned     
          // check parent is serial port  
          $ModuleID = IPS_GetInstance($SerialPortInstanceID)['ModuleInfo']['ModuleID'];      
          if ( $ModuleID !== '{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}' ) return false; // wrong parent type
	  if ( IPS_GetProperty( $SerialPortInstanceID, "Open" ) != true ) return false; // com port closed	
		
	  // send 0x04		 
	  $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", 
						    "Buffer" => utf8_encode("\x04") )));	
		
	  // Close serial port
	  if ( IPS_GetProperty( $SerialPortInstanceID, "Open" ) != false )
          {
	        IPS_SetProperty( $SerialPortInstanceID, "Open", false );
	        IPS_ApplyChanges( $SerialPortInstanceID );
          }
	  $this->SetBuffer( "PortState", ViessControl::COMPORT_CLOSED );	
		
	  return true;			
        }
       
        //=== Tool Functions ============================================================================================
        function strToHex($string){
            $hex = '';
            for ($i=0; $i<strlen($string); $i++){
                $ord = ord($string[$i]);
                $hexCode = dechex($ord);
                $hex .= substr('0'.$hexCode, -2);
            }
            return strToUpper($hex);
	}
	    
        function hexToStr($hex){
            $string='';
            for ($i=0; $i < strlen($hex)-1; $i+=2){
                $string .= chr(hexdec($hex[$i].$hex[$i+1]));
            }
            return $string;
        }  
	    
        //=== Module Prefix Functions ===================================================================================
        /* Own module functions called via the defined prefix ViessControl_* 
        *
        * - ViessControl_identifyHeatingControl($id);
        *
        */
        
        public function IdentifyHeatingControl() {
          /* identify the connected Heating Control */
          
          // Init Communication
          if ( $this->startCommunication() === true ) {
            // Init successful
		  
            // send command to request identification data from control ( 0x41 0x05 0x00 0x01 0x00 0xF8 0x02 0x00 ) (Protocol 300)
            if ( $this->GetBuffer( "PortState" ) == ViessControl::COMPORT_READY )
	    {
	      // Clear old data
	      $this->SetBuffer( "ReceiveBuffer", "" );
              $this->SetBuffer( "RequestedData", "" );
	      // send request
	      $this->SetBuffer( "PortState", ViessControl::COMPORT_DATA_REQUESTED ); // to be done before request is send
	      $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", 
                                                        "Buffer" => utf8_encode("\x41\x05\x00\x01\x00\xF8\x02\x00"))));
	      $tryCounter = 10;
	      do {
                sleep(1); // wait 1 second
	        $tryCounter--;	  
	      } while ( $this->GetBuffer( "PortState" ) != ViessControl::COMPORT_READY AND $tryCounter > 0 );
		    	   
	      $this->sendDebug( "Viess", "Requested Data: ".$this->GetBuffer( "RequestedData" ), 0 );
		    
              // End Communication
              $this->endCommunication();
              return $this->GetBuffer( "ReceiveBuffer" );
            }
	  }
          else return false;
        }
        
    }
?>
