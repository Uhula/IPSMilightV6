<?

/*
	http://192.168.2.131/imagep/picture.jpg

   UART-Docu: http://www.sabreadv.com/wp-content/uploads/HF-LPC100-User-Manual-V1.0.pdf

*/

class IPSMilightV6 extends IPSModule {

   const ZONE_ALL = 0;
   const ZONE_1   = 1;
   const ZONE_2   = 2;
   const ZONE_3   = 3;
   const ZONE_4   = 4;

   const TYPE_RGBW   = 0;
   const TYPE_BRIDGE = 1;
   const TYPE_RGBWW  = 2;

   const MODE_OFF   = 0;
   const MODE_COLOR = 1;
   const MODE_WHITE = 2;
   const MODE_NIGHT = 3;
   const MODE_DISCO = 4;

   // Log-Level Konstante
   const LOG_NONE     = 0x00;
   const LOG_ERRORS   = 0x01;
   const LOG_WARNINGS = 0x02;
   const LOG_HINTS    = 0x04;
   const LOG_MESSAGE  = 0x10;
   const LOG_ECHO     = 0x20;
   private $LogLevel       = self::LOG_ERRORS | self::LOG_WARNINGS | self::LOG_ECHO | self::LOG_HINTS;

   private $host = "";
   private $port = 0;
   public $MACAdr = "";
   public $SessionID1 = -1;
   public $SessionID2 = -1;
   private $sendRetries = 5;
   private $receiveRetries = 5;
   private $SequenceNbr = 1;

   private static $CMD_PreAmble = array(0x80, 0x00, 0x00, 0x00, 0x11);
   private static $CMD_GetSessionID = array( 0x20, 0x00, 0x00, 0x00, 0x16, 0x02, 0x62, 0x3A, 0xD5, 0xED, 0xA3, 0x01, 0xAE, 0x08, 0x2D, 0x46, 0x61, 0x41, 0xA7, 0xF6, 0xDC, 0xAF, 0xD3, 0xE6, 0x00, 0x00, 0x1E );

   private static $CMDS = array(
      self::TYPE_RGBW => array(
         "switchOn"      => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "switchOff"     => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x02, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "setColor"      => array( 0x31, 0x00, 0x00, 0x07, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00), // 9th=zone
         "setBrightness" => array( 0x31, 0x00, 0x00, 0x07, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "switchOnWhite" => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x05, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "switchOnNight" => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x06, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "setDiscoMode"  => array( 0x31, 0x00, 0x00, 0x07, 0x04, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone 6th hex values 0x01 to 0x09
         "incDiscoSpeed" => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x03, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "decDiscoSpeed" => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x04, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "setLinkMode"   => array( 0x3D, 0x00, 0x00, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "setUnlinkMode" => array( 0x3E, 0x00, 0x00, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00)  // 9th=zone
         ),
      self::TYPE_BRIDGE => array(
         "switchOn"      => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x03, 0x00, 0x00, 0x00, 0x01 ),
         "switchOff"     => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x04, 0x00, 0x00, 0x00, 0x01 ),
         "setColor"      => array( 0x31, 0x00, 0x00, 0x00, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x01 ),
         "setBrightness" => array( 0x31, 0x00, 0x00, 0x00, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x01 ),
         "switchOnWhite" => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x05, 0x00, 0x00, 0x00, 0x01 ),
         "setDiscoMode"  => array( 0x31, 0x00, 0x00, 0x00, 0x04, 0x01, 0x00, 0x00, 0x00, 0x01 ), // 6th hex values 0x01 to 0x09
         "incDiscoSpeed" => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x02, 0x00, 0x00, 0x00, 0x01 ),
         "decDiscoSpeed" => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x01, 0x00, 0x00, 0x00, 0x01 )
	     ),
	  self::TYPE_RGBWW => array(
         "switchOn"      => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "switchOff"     => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x02, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 01 BA BA BA BA = Set Color to Blue (0xBA) (0xFF = Red, D9 = Lavender, BA = Blue, 85 = Aqua, 7A = Green, 54 = Lime, 3B = Yellow, 1E = Orange)
         "setColor"      => array( 0x31, 0x00, 0x00, 0x08, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00), // 9th=zone
                            // 31 00 00 08 02 SS 00 00 00 = Saturation (SS hex values 0x00 to 0x64 : examples: 00 = 0%, 19 = 25%, 32 = 50%, 4B, = 75%, 64 = 100%)
         "setSaturation" => array( 0x31, 0x00, 0x00, 0x08, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 03 BN 00 00 00 = BrightNess (BN hex values 0x00 to 0x64 : examples: 00 = 0%, 19 = 25%, 32 = 50%, 4B, = 75%, 64 = 100%)
         "setBrightness" => array( 0x31, 0x00, 0x00, 0x08, 0x03, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 05 KV 00 00 00 = Kelvin (KV hex values 0x00 to 0x64 : examples: 00 = 2700K (Warm White), 19 = 3650K, 32 = 4600K, 4B, = 5550K, 64 = 6500K (Cool White))
         "setKelvin"     => array( 0x31, 0x00, 0x00, 0x08, 0x05, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "switchOnWhite" => array( 0x31, 0x00, 0x00, 0x08, 0x05, 0x64, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "switchOnNight" => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x05, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 06 MO 00 00 00 = Mode Number MO hex values 0x01 to 0x09
         "setDiscoMode"  => array( 0x31, 0x00, 0x00, 0x08, 0x06, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone 6th hex values 0x01 to 0x09
         "incDiscoSpeed" => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x03, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         "decDiscoSpeed" => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x04, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 3D 00 00 08 00 00 00 00 00 = Link (Sync Bulb within 3 seconds of lightbulb socket power on)
         "setLinkMode"   => array( 0x3D, 0x00, 0x00, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00), // 9th=zone
	                        // 3E 00 00 08 00 00 00 00 00 = UnLink (Clear Bulb within 3 seconds of lightbulb socket power on)
         "setUnlinkMode" => array( 0x3E, 0x00, 0x00, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00)  // 9th=zone
	     )
	  );


   public function Create() {
     //Never delete this line!
     parent::Create();

     //These lines are parsed on Symcon Startup or Instance creation
     //You cannot use variables here. Just static values.
     $this->RegisterPropertyString("URL", "255.255.255.255");
     $this->RegisterPropertyInteger("Port", 5987);
     $this->RegisterPropertyInteger("Type", self::ZONE_ALL);
     $this->RegisterPropertyInteger("Zone", self::TYPE_RGBWW);
     //$this->RegisterPropertyBoolean("CardShadow", TRUE);
   }

   public function Destroy() {
     //Never delete this line!
     parent::Destroy();
   }

   public function ApplyChanges() {
     //Never delete this line!
     parent::ApplyChanges();

     $this->RegisterProfileIntegerAssociation("MilightV6.Type", "", "", "",
        [
         [self::TYPE_RGBW,"RGBW","",-1],
         [self::TYPE_BRIDGE,"BRIDGE","",-1],
         [self::TYPE_RGBWW,"RGBWW","",-1]
      ], 1);
     $this->RegisterProfileIntegerAssociation("MilightV6.Zone", "", "", "",
        [
         [self::ZONE_ALL,"Alle Zonen","",-1],
         [self::ZONE_1,"Zone 1","",-1],
         [self::ZONE_2,"Zone 2","",-1],
         [self::ZONE_3,"Zone 2","",-1],
         [self::ZONE_4,"Zone 4","",-1]
      ], 1);
     $this->RegisterProfileIntegerAssociation("MilightV6.Mode", "", "", "",
          [
           [self::MODE_OFF,"Aus","",-1],
           [self::MODE_COLOR,"Farbig","",-1],
           [self::MODE_WHITE,"Weiß","",-1],
           [self::MODE_NIGHT,"Nacht","",-1],
           [self::MODE_DISCO,"Disco","",-1]
        ], 1);

     //Variablen erstellen
     $this->RegisterVariableInteger("Hue", "Farbe 0..255", "",0);
     $this->RegisterVariableInteger("Saturation", "Sättigung 0..100%", "",1);
     $this->RegisterVariableInteger("Brightness", "Helligkeit 0..100%","",2);
     $this->RegisterVariableInteger("Mode", "Modus", "MilightV6.Mode",3);

     $this->EnableAction("Hue");
     $this->EnableAction("Saturation");
     $this->EnableAction("Brightness");
     $this->EnableAction("Mode");

     /*
     $this->SetValueInteger("Hue", $this->ReadPropertyInteger("Hue") );
     $this->SetValueInteger("Saturation", $this->ReadPropertyInteger("Saturation"));
     $this->SetValueInteger("Brightness", $this->ReadPropertyInteger("Brightness"));
     $this->SetValueInteger("Mode", $this->ReadPropertyBoolean("Mode"));
     */

     $this->Update();
   }


   public function Update() {
      $result = false;
      $this->host = $this->ReadPropertyString("URL");
      $this->port = $this->ReadPropertyInteger("Port");
      $type = $this->ReadPropertyInteger("Type");
      $zone = $this->ReadPropertyInteger("Zone");

      if (   ( $this->host == "" )
          or ( $this->port == 0 )
          or ( $zone < self::ZONE_ALL )  or ( $zone > self::ZONE_4 )
          or ( $type < self::TYPE_RGBW ) or ( $type > self::TYPE_RGBWW )
         ) {
         $this->SetStatus(201);
         return false;
      } else {
         $this->SetStatus(102);
      }

     // Anwenden der Änderungen
     switch ($this->GetValueInteger("Mode")) {
        case SELF::MODE_OFF : //off
           $this->switchOff($type, $zone);
           break;
        case SELF::MODE_COLOR : //farbig
           $this->setColor($type, $zone, $this->GetValueInteger("Hue"));
           break;
        case SELF::MODE_WHITE : //weiß
           $this->switchOnWhite($type, $zone);
           break;
        case SELF::MODE_NIGHT : //Nacht
           $this->switchOnNight($type, $zone);
           break;
        case SELF::MODE_DISCO : //Disco
           $this->setDiscoMode($type, $zone, 0);
           break;

     }
     return $result;
   }

   public function _SetHue(integer $hue) {
     $hue = $hue & 0xff;
     $this->SetValueInteger("Hue", $hue );
     return $this->Update();
   }

   public function _SetSaturation(integer $saturation) {
     $saturation = $saturation & 0x64;
     $this->SetValueInteger("Saturation", $saturation );
     return $this->Update();
   }

   public function _SetBrightness(integer $brightness) {
     $brightness = $brightness & 0x64;
     $this->SetValueInteger("Brightness", $brightness );
     return $this->Update();
   }

   public function _SetMode(integer $mode) {
     $this->SetValueInteger("Mode", $mode );
     return $this->Update();
   }

   public function RequestAction($Ident, $Value) {
     switch($Ident) {
       case "Hue":
         $this->_SetHue( $Value );
         break;
       case "Saturation":
         $this->_SetSaturation( $Value );
         break;
       case "Brightness":
         $this->_SetBrightness( $Value );
         break;
       case "Mode":
          $this->_SetMode( $Value );
          break;
       default:
         throw new Exception("Invalid ident");
         }
   }

   /* IPS Helper
   --------------------------------------------------------------------------------*/
   private function SetValueInteger($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueInteger($id) <> $value) {
       SetValueInteger($id, $value);
       return true;
     }
     return false;
   }
   private function GetValueInteger($Ident) {
     $id = $this->GetIDForIdent($Ident);
     $val = GetValueInteger($id);
     return $val;
   }

   private function SetValueBoolean($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueBoolean($id) <> $value) {
       SetValueBoolean($id, boolval($value));
       return true;
     }
     return false;
   }
   private function GetValueBoolean($Ident) {
     $id = $this->GetIDForIdent($Ident);
     $val = GetValueBoolean($id);
     return $val;
   }

   private function SetValueFloat($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueFloat($id) <> $value) {
       SetValueFloat($id, $value);
       return true;
     }
     return false;
   }

   private function SetValueString($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueString($id) <> $value) {
       SetValueString($id, $value);
       return true;
     }
     return false;
   }
   private function GetValueString($Ident) {
     $id = $this->GetIDForIdent($Ident);
     $val = GetValueString($id);
     return $val;
   }

   protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
     if (!IPS_VariableProfileExists($Name)) {
     	IPS_CreateVariableProfile($Name, 1);
     } else {
     	$profile = IPS_GetVariableProfile($Name);
     	if ($profile['ProfileType'] != 1) {
     		throw new Exception("Variable profile type does not match for profile ".$Name);
     	}
     }

     IPS_SetVariableProfileIcon($Name, $Icon);
     IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
     IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
 	}

   protected function RegisterProfileIntegerAssociation($Name, $Icon, $Prefix, $Suffix, $Associations, $StepSize) {
     if ( sizeof($Associations) === 0 ){
       $MinValue = 0;
       $MaxValue = 0;
     } else {
       $MinValue = $Associations[0][0];
       $MaxValue = $Associations[sizeof($Associations)-1][0];
     }

     $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize);

     foreach($Associations as $Association) {
       IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
     }

   }

   protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {

         if(!IPS_VariableProfileExists($Name)) {
             IPS_CreateVariableProfile($Name, 0);
         } else {
             $profile = IPS_GetVariableProfile($Name);
             if($profile['ProfileType'] != 0)
             throw new Exception("Variable profile type does not match for profile ".$Name);
         }

         IPS_SetVariableProfileIcon($Name, $Icon);
         IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
         IPS_SetVariableProfileValues($Name, boolval($MinValue), boolval($MaxValue), $StepSize);
     }

   protected function RegisterProfileBooleanAssociation($Name, $Icon, $Prefix, $Suffix, $Associations, $StepSize) {
         if ( sizeof($Associations) === 0 ){
             $MinValue = 0;
             $MaxValue = 0;
         } else {
             $MinValue = $Associations[0][0];
             $MaxValue = $Associations[sizeof($Associations)-1][0];
         }

         $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize);

         foreach($Associations as $Association) {
             IPS_SetVariableProfileAssociation($Name, boolval($Association[0]), $Association[1], $Association[2], $Association[3]);
         }

     }

/* ================================================================
   private class functions
   ================================================================*/

  /* Log
  --------------------------------------------------------------------
  Schreibt die $msg indie IPSymcon Log-Konsole. Class- und Functionname
  der aufrufenden Funktion werden automatisch vorangestellt
  */
  private function Log($msg,$loglevel=self::LOG_HINTS) {
    if ($loglevel & $this->LogLevel) {
       $trace = debug_backtrace();
       $function = $trace[1]['function'];
	   if (self::LOG_MESSAGE & $this->LogLevel)
          IPS_LogMessage(__CLASS__, "[".$function."] " . $msg ); //
	   if (self::LOG_ECHO & $this->LogLevel)
	      echo __CLASS__."[".$function."] " . $msg ."\n";
	}
  }

   //
   private function RGB2HSL($r, $g, $b) {
      $r = $r / 255; $g = $g / 255; $b = $b / 255;
      $max = max($r, $g, $b); $min = min($r, $g, $b);
      $l = ($max + $min) / 2;
      $d = $max - $min;
      $h = '';
      if ($d == 0) {
         $h = $s = 0;
      } else {
         $s = $d / (1 - abs(2 * $l - 1));
         switch ($max) {
            case $r:
               $h = 60 * fmod((($g - $b) / $d), 6);
               if ($b > $g) { $h += 360; }
               break;
            case $g:
               $h = 60 * (($b - $r) / $d + 2);
               break;
            case $b:
               $h = 60 * (($r - $g) / $d + 4);
               break;
          }
      }
      return array($h, $s, $l);
    }

    private function HEX2HSL($hexcolor) {
     list($r,$g,$b) = array_map('hexdec',str_split(ltrim($hexcolor, '#'),2));
     return $this->RGB2HSL( $r, $g, $b );
	}

    private function HSL2MilightColor($hsl) {
       return intval($hsl[0] / 360.0 * 255.0) & 0xFF;
    }

   private function RGB2MilightColor($r, $g, $b) {
     return $this->HSL2MilightColor( $this->RGB2HSL($r, $g, $b) );
   }
   // #RRGGBB oder RRGGBB  oder #rrggbb oder rrggbb
   private function HEX2MilightColor($hexcolor) {
     list($r,$g,$b) = array_map('hexdec',str_split(ltrim($hexcolor, '#'),2));
     return $this->RGB2MilightColor( $r, $g, $b );
   }

   private function receiveString($socket) {
   $res = "";

   $receiveRetry = 0;
   while ( ($receiveRetry < $this->receiveRetries) and ($res=="") ) {
      if (false !== ($receiveBytes = @socket_recv($socket, $buf, 128, MSG_DONTWAIT) ) ) {
         $res = $buf;
      } else {
         IPS_Sleep(100);
         $receiveRetry++;
         $this->Log("Empfangsversuch: $receiveRetry / $this->receiveRetries");
      }
   }
	$this->Log(chunk_split(bin2hex( $res ),2," "));
   return $res;
 }

 private function sendString($socket, $buf) {
   $sendRetry=0;
	$sentBytes=0;
	$this->Log(chunk_split(bin2hex( $buf ),2," "));

   while ( ($sendRetry < $this->sendRetries) and ($sentBytes==0) ) {
      $sentBytes = @socket_send($socket, $buf, strlen($buf), 0);
		//$sentBytes=22;
      $sendRetry++;
      if ($sentBytes==0)
         $this->Log("Sendeversuch: .$sendRetry / $this->sendRetries");
      }
   return $sentBytes;
   }

   private function sendByteArray($socket, Array $bytes) {
      $buf = vsprintf(str_repeat('%c', count($bytes)), $bytes);
      return $this->sendString($socket, $buf);
   }

   private function getSessionID($socket) {
      $sentBytes = $this->sendByteArray($socket, self::$CMD_GetSessionID );
		if ($sentBytes!=0) {
		   IPS_Sleep(100);
         $receive = $this->receiveString($socket);
         if (strlen($receive) > 20) {
            //$hex = chunk_split(bin2hex( $buf),2," ");
            $this->MACAdr = substr(chunk_split(bin2hex( substr($receive,8,6) ),2,":"),0,-1);
            $this->SessionID1 = ord($receive[19]);
            $this->SessionID2 = ord($receive[20]);
            return true;
         }
      }
      return false;
   }
   /* */
   public function detectBridges() {
      $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
      if (!is_resource($socket)) {
         $this->Log("Socket kann nicht geöffnet werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         return false;
      }
      if (!socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1)) {
         $this->Log("Socket-Option SO_BROADCAST kann nicht gesetzt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
		};

      if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0))) {
         $this->Log("Socket-Option SO_RCVTIMEO kann nicht gesetzt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
		};


		$result = socket_connect($socket, "255.255.255.255", 48899);
//		$result = socket_connect($socket, "192.168.2.131", 48899);

      if ($result === false) {
         $this->Log("Socket kann nicht mit $this->host:$this->port verbunden werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
      }

      $sentBytes = $this->sendString($socket, "HF-A11ASSISTHREAD");
		if ($sentBytes>0) {
			for ($i=0; $i<5; $i++) {
   			$receive = $this->receiveString($socket);
            //each wifi bridge responds with one response at time. so call receive again until 1 second is up.
            // returns a string containing: IP address of the wifi bridge, the unique MAC address, and the name(which is always the same for v6, and the name is empty for v5 bridges) there is always two commas present regardless of v5 or v6 wifi bridge.
            // 10.1.1.27,ACCF23F57AD4,HF-LPB100
            // 10.1.1.31,ACCF23F57D80,HF-LPB100
//            $this->Log("Found ".chunk_split(bin2hex( $receive ),2,":"));
            $this->Log("Found ".$receive);
		      IPS_Sleep(1000);
      	}
         socket_close($socket);
         return true;
      }
		return false;
   }

   /* sendCommand
      UDP Hex Send Format: 80 00 00 00 11 {WifiBridgeSessionID1} {WifiBridgeSessionID2} 00 {SequenceNumber} 00 {COMMAND} {ZONE NUMBER} 00 {Checksum}
      UDP Hex Response: 88 00 00 00 03 00 {SequenceNumber} 00
   */
   private function sendCommand( $cmd ) {
      $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
      if (!is_resource($socket)) {
         $this->Log("Socket kann nicht geöffnet werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         return false;
      }

      if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0))) {
         $this->Log("Socket-Option SO_RCVTIMEO kann nicht gesetzt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
		};

		$result = socket_connect($socket, $this->host, $this->port);
      if ($result === false) {
         $this->Log("Socket kann nicht mit $this->host:$this->port verbunden werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
      }

      if (!$this->getSessionID($socket)) {
         $this->Log("SessionID kann nicht ermittelt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
      }

      $bytes = self::$CMD_PreAmble;
      $bytes[] = $this->SessionID1;
      $bytes[] = $this->SessionID2;
      $bytes[] = 0x00;
      $bytes[] = $this->SequenceNbr;
      $bytes[] = 0x00;
      $bytes = array_merge($bytes, $cmd);
      $bytes[] = 0x00;
      $checksum = 0;
      for ($i=10; $i<=10+10; $i++) $checksum = $checksum + $bytes[$i];
      $bytes[] = $checksum & 0xFF;

      $receive="";
      $sentBytes = $this->sendByteArray($socket, $bytes);
		if ($sentBytes!=0) {
		   IPS_Sleep(100);
         $receive = $this->receiveString($socket);
         socket_close($socket);

         if ( (strlen($receive) >= 8) and ($receive[7]==0) ){
            $this->SequenceNbr = $this->SequenceNbr & 0xFF;
            return true;
         }
	   }

	   $msg = vsprintf(str_repeat('%c', count($bytes)), $bytes);
	   $msg=chunk_split(bin2hex($msg), 2, ' ');

	   $rec=chunk_split(bin2hex($receive), 2, ' ');

      $this->Log("Fehler: send=$msg receive=$rec ",self::LOG_ERRORS);
      return false;
   }

   //
   private function executeCommand($type, $zone, $cmd, $patchcmd=[]) {
      if (!(array_key_exists ($type, self::$CMDS ))) {
         $this->Log("Fehler: Lampen-Type=$type ist unbekannt ",self::LOG_ERRORS);
         return false;
     }
      if (!(array_key_exists ($cmd, self::$CMDS[$type] ))) {
         $this->Log("Fehler: Lampen-Befehl=$cmd ist unbekannt ",self::LOG_ERRORS);
         return false;
     }

      $cmdarray=self::$CMDS[$type][$cmd];

      // patch optional patchcmd
      foreach ($patchcmd as $key => $value)
         $cmdarray[$key]=$value;

      // patch zone (nicht bei BRIDGE)
      if ( ($type!=self::TYPE_BRIDGE) and (count($cmd)>9) )
         $cmdarray[9] = $this->zone;

      if ($cmdarray<>[])
         return $this->sendCommand( $cmdarray );
      else
         return false;
   }

/* ================================================================
   public functions
   ================================================================*/
   //
   public function switchOn($type, $zone) {
      return $this->executeCommand($type, $zone, "switchOn");
   }
   //
   public function switchOff($type, $zone) {
      return $this->executeCommand($type, $zone, "switchOff");
   }

   // Color 00..FF
   public function setColor($type, $zone,  $color ) {
      return $this->executeCommand($type, $zone, "setColor", array(5=>$color, 6=>$color, 7=>$color, 8=>$color ) );
   }

   // Color als hex-color #rrggbb oder rrggbb
   public function setColorHEX($type, $zone, $hexcolor) {

      $hsl = $this->HEX2HSL($hexcolor);

      switch ($type) {
	     case self::TYPE_RGBW :
            $this->setColor($type, $zone, $this->HSL2MilightColor($hsl));
            $this->setBrightness($type, $zone, $hsl[2]*100 );
		    break;
	     case self::TYPE_BRIDGE : ;
            $this->setColor($type, $zone, $this->HSL2MilightColor($hsl));
            $this->setBrightness($type, $zone, $hsl[2]*100 );
		    break;
	     case self::TYPE_RGBWW : ;
            $this->setColor($type, $zone, $this->HSL2MilightColor($hsl));
            $this->setSaturation($type, $zone,  $hsl[1]*100 );
            $this->setBrightness($type, $zone,  $hsl[2]*100 );
		    break;
	  }
   }


   // Brightness 0..100%
   public function setBrightness($type, $zone, $brightness) {
	  $brightness = intval( min( 100, max( 0, $brightness) ) / 100 * 0x64);
      return $this->executeCommand($type, $zone, "setBrightness", array(5=>$brightness) );
   }

   // Saturation 0..100%
   public function setSaturation($type, $zone, $saturation) {
	  $saturation = 0x64 - (intval( min( 100, max( 0, $saturation) ) / 100 * 0x64));
      return $this->executeCommand($type, $zone, "setSaturation", array(5=>$saturation) );
   }

   // Kelvin 0=2700k .. 64=6500k
   public function setKelvin($type, $zone, $kelvin) {
	  $kelvin = intval( ( min( 6500, max( 2700, $kelvin) ) - 2700 ) / (6500-2700) * 0x64);
      return $this->executeCommand($type, $zone, "setKelvin", array(5=>$kelvin) );
   }

   //
   public function switchOnWhite($type, $zone) {
      return $this->executeCommand($type, $zone, "switchOnWhite" );
   }

   //
   public function switchOnNight($type, $zone) {
      return $this->executeCommand($type, $zone, "switchOnNight" );
   }

   // Disco Mode
   public function setDiscoMode($mode) {
	  $mode = intval( min( 9, max( 0, $mode) ) );
      return $this->executeCommand("setDiscoMode", array(5=>$mode) );
   }

   //
   public function decDiscoSpeed($type, $zone) {
      return $this->executeCommand($type, $zone, "decDiscoSpeed" );
   }
   //
   public function incDiscoSpeed($type, $zone) {
      return $this->executeCommand($type, $zone, "incDiscoSpeed" );
   }
   //
   public function setLinkMode($type) {
      return $this->executeCommand($type, self::ZONE_ALL, "setLinkMode" );
   }

   //
   public function setUnlinkMode($type) {
      return $this->executeCommand($type, self::ZONE_ALL, "setUnlinkMode" );
   }


}


?>
