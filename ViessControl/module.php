<?
    class ViessControl extends IPSModule {
 
        public function __construct($InstanceID) {
          /* Constructor is called before each function call */
          parent::__construct($InstanceID);
        }
 
        public function Create() {
          /* Create is called ONCE on Instance creation and start of IP-Symcon.
             Status-Variables und Modul-Properties for permanent usage should be created here  */
          parent::Create(); 
        }
 
        public function ApplyChanges() {
          /* Called on 'apply changes' in the configuration UI and after creation of the instance */
          parent::ApplyChanges();
        }
 
        //=== Module Functions =========================================================================================
   
        public function ReceiveData($JSONString) {
          // Receive data from serial port I/O
          $data = json_decode($JSONString);
          IPS_LogMessage("ReceiveData", utf8_decode($data->Buffer));
 
          // Process data
 
        }
        
        //=== Private Functions for Communication handling with Vitotronic ==============================================
        private function startCommunication() {
          // open serial port (parent)
          $SerialPortInstanceID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
          if ( $SerialPortInstanceID == false ) return false;
          echo $SerialPortInstanceID;
            
          
            
            
          // send 0x04 to bring communication into a defined state
          // send 0x16 0x00 0x00 till Vitotronic has answered with 0x06 (Muss über eine property gesetzt im Receive gehandhabt werden)
        
          // Fehlerhandling / nicht unendlich laufen
          return true;
        } 
        
        private function endCommunication() {
          // send 0x04
          // close serial port (parent)
        }
        
        //=== Module Prefix Functions ===================================================================================
        /* Own module functions called via the defined prefix ViessControl_* 
        *
        * - ViessControl_identifyHeatingControl($id);
        *
        */
        
        public function identifyHeatingControl() {
          /* identify the connected Heating Control */
          echo "ViessControl_identifyHeatingControl for instance ".$this->InstanceID;
         
          // Init Communication
          if ( $this->startCommunication() === true ) {
            // Init successful
            // send command to request identification data from control ( 0x41 0x05 0x00 0x01 0x00 0xF8 0x02 0x00 )
            //$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", 
            //                                          "Buffer" => $data->Buffer)));
            // End Communication
            $this->endCommunication();
          }
          else
          {
             // Init of Communication failed
          }
            
        }
        
        
    }
?>
