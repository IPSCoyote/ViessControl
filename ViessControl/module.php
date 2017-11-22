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
        
        //=== Module Prefix Functions ===================================================================================
        /* Own module functions called via the defined prefix ViessControl_* 
        *
        * ViessControl_identifyHeatingControl($id);
        *
        */
        
        public function identifyHeatingControl($ID) {
          /* identify the connected Heating Control */
          echo "ViessControl_identifyHeatingControl for instance ".$ID;
         
          // Open serial port if needed
            
          // send command to request identification data from control
          $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", 
                                                    "Buffer" => $data->Buffer)));
          
        }
    }
?>
