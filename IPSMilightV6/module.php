<?

/*  IPS Modul Milight V6
    ---------------------------------------------------------------------------
    Modul zum Ansteuern der Milight-Gateways iBox2 mit der Treiberversion 6,
    welche auch RGBWW Lampen unterstützt.

   Info:  https://github.com/Uhula/IPSMilightV6.git
   (c) 2017 Uhula, use on your own risc

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
   const MODE_LINK  = 5;
   const MODE_UNLINK= 6;

   // Log-Level Konstante
   const LOG_NONE     = 0x00;
   const LOG_ERRORS   = 0x01;
   const LOG_WARNINGS = 0x02;
   const LOG_HINTS    = 0x04;
   const LOG_MESSAGE  = 0x10;
   const LOG_ECHO     = 0x20;
   private $LogLevel       = self::LOG_ERRORS | self::LOG_WARNINGS | self::LOG_MESSAGE | self::LOG_HINTS;

   public $MACAdr = "";
   public $SessionID1 = -1;
   public $SessionID2 = -1;

   private $SequenceNbr = 1;
   private $sendRetries = 1;
   private $receiveRetries = 1;

   private static $CMD_PreAmble = array(0x80, 0x00, 0x00, 0x00, 0x11);
   private static $CMD_GetSessionID = array( 0x20, 0x00, 0x00, 0x00, 0x16, 0x02, 0x62, 0x3A, 0xD5, 0xED, 0xA3, 0x01, 0xAE, 0x08, 0x2D, 0x46, 0x61, 0x41, 0xA7, 0xF6, 0xDC, 0xAF, 0xD3, 0xE6, 0x00, 0x00, 0x1E );

   const CMD_SWITCH_ON  = 0;
   const CMD_SWITCH_OFF = 1;
   const CMD_SET_COLOR  = 2;
   const CMD_SET_SATURATION = 3;
   const CMD_SET_BRIGHTNESS = 4;
   const CMD_SET_TEMPERATURE = 5;
   const CMD_SWITCH_ON_WHITE = 6;
   const CMD_SWITCH_ON_NIGHT = 7;
   const CMD_SET_DISCO_PROGRAM = 8;
   const CMD_INC_DISCO_SPEED = 9;
   const CMD_DEC_DISCO_SPEED = 10;
   const CMD_SET_LINK_MODE = 11;
   const CMD_SET_UNLINK_MODE = 12;

   private static $CMDS = array(
      self::TYPE_RGBW => array(
         self::CMD_SWITCH_ON => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SWITCH_OFF => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x02, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SET_COLOR => array( 0x31, 0x00, 0x00, 0x07, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00), // 9th=zone
         self::CMD_SET_BRIGHTNESS => array( 0x31, 0x00, 0x00, 0x07, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SWITCH_ON_WHITE => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x05, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SWITCH_ON_NIGHT => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x06, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SET_DISCO_PROGRAM=>array( 0x31, 0x00, 0x00, 0x07, 0x04, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone 6th hex values 0x01 to 0x09
         self::CMD_INC_DISCO_SPEED => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x03, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_DEC_DISCO_SPEED => array( 0x31, 0x00, 0x00, 0x07, 0x03, 0x04, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SET_LINK_MODE => array( 0x3D, 0x00, 0x00, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SET_UNLINK_MODE => array( 0x3E, 0x00, 0x00, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00)  // 9th=zone
         ),
      self::TYPE_BRIDGE => array(
         self::CMD_SWITCH_ON => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x03, 0x00, 0x00, 0x00, 0x01 ),
         self::CMD_SWITCH_OFF => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x04, 0x00, 0x00, 0x00, 0x01 ),
         self::CMD_SET_COLOR => array( 0x31, 0x00, 0x00, 0x00, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x01 ),
         self::CMD_SET_BRIGHTNESS => array( 0x31, 0x00, 0x00, 0x00, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x01 ),
         self::CMD_SWITCH_ON_WHITE => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x05, 0x00, 0x00, 0x00, 0x01 ),
         self::CMD_SET_DISCO_PROGRAM => array( 0x31, 0x00, 0x00, 0x00, 0x04, 0x01, 0x00, 0x00, 0x00, 0x01 ), // 6th hex values 0x01 to 0x09
         self::CMD_INC_DISCO_SPEED => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x02, 0x00, 0x00, 0x00, 0x01 ),
         self::CMD_DEC_DISCO_SPEED => array( 0x31, 0x00, 0x00, 0x00, 0x03, 0x01, 0x00, 0x00, 0x00, 0x01 )
	     ),
	  self::TYPE_RGBWW => array(
         self::CMD_SWITCH_ON => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SWITCH_OFF => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x02, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 01 BA BA BA BA = Set Color to Blue (0xBA) (0xFF = Red, D9 = Lavender, BA = Blue, 85 = Aqua, 7A = Green, 54 = Lime, 3B = Yellow, 1E = Orange)
         self::CMD_SET_COLOR => array( 0x31, 0x00, 0x00, 0x08, 0x01, 0xBA, 0xBA, 0xBA, 0xBA, 0x00), // 9th=zone
                            // 31 00 00 08 02 SS 00 00 00 = Saturation (SS hex values 0x00 to 0x64 : examples: 00 = 0%, 19 = 25%, 32 = 50%, 4B, = 75%, 64 = 100%)
         self::CMD_SET_SATURATION => array( 0x31, 0x00, 0x00, 0x08, 0x02, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 03 BN 00 00 00 = BrightNess (BN hex values 0x00 to 0x64 : examples: 00 = 0%, 19 = 25%, 32 = 50%, 4B, = 75%, 64 = 100%)
         self::CMD_SET_BRIGHTNESS => array( 0x31, 0x00, 0x00, 0x08, 0x03, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 05 KV 00 00 00 = Kelvin (KV hex values 0x00 to 0x64 : examples: 00 = 2700K (Warm White), 19 = 3650K, 32 = 4600K, 4B, = 5550K, 64 = 6500K (Cool White))
         self::CMD_SET_TEMPERATURE => array( 0x31, 0x00, 0x00, 0x08, 0x05, 0xBE, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SWITCH_ON_WHITE => array( 0x31, 0x00, 0x00, 0x08, 0x05, 0x64, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_SWITCH_ON_NIGHT => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x05, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 31 00 00 08 06 MO 00 00 00 = Mode Number MO hex values 0x01 to 0x09
         self::CMD_SET_DISCO_PROGRAM =>array( 0x31, 0x00, 0x00, 0x08, 0x06, 0x01, 0x00, 0x00, 0x00, 0x00), // 9th=zone 6th hex values 0x01 to 0x09
         self::CMD_INC_DISCO_SPEED => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x03, 0x00, 0x00, 0x00, 0x00), // 9th=zone
         self::CMD_DEC_DISCO_SPEED => array( 0x31, 0x00, 0x00, 0x08, 0x04, 0x04, 0x00, 0x00, 0x00, 0x00), // 9th=zone
                            // 3D 00 00 08 00 00 00 00 00 = Link (Sync Bulb within 3 seconds of lightbulb socket power on)
         self::CMD_SET_LINK_MODE => array( 0x3D, 0x00, 0x00, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00), // 9th=zone
	                        // 3E 00 00 08 00 00 00 00 00 = UnLink (Clear Bulb within 3 seconds of lightbulb socket power on)
         self::CMD_SET_UNLINK_MODE => array( 0x3E, 0x00, 0x00, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00)  // 9th=zone
	     )
	  );


   public function Create() {
      //Never delete this line!
      parent::Create();

      //These lines are parsed on Symcon Startup or Instance creation
      //You cannot use variables here. Just static values.
      $this->RegisterPropertyString("URL", "192.168.2.31");
      $this->RegisterPropertyInteger("Port", 5987);
      $this->RegisterPropertyInteger("Type", self::TYPE_RGBWW);
      $this->RegisterPropertyInteger("Zone", self::ZONE_ALL);
      $p ='[';
      $p.='{"id":10, "name":"Rot","Mode":1,"ColorHue":0,"ColorSaturation":100,"ColorBrightness":100}';
      $p.=',{"id":11, "name":"Grün","Mode":1,"ColorHue":120,"ColorSaturation":100,"ColorBrightness":100}';
      $p.=',{"id":12, "name":"Blau","Mode":1,"ColorHue":240,"ColorSaturation":100,"ColorBrightness":100}';
      $p.=',{"id":20, "name":"Warmweiß 25%","Mode":2,"WhiteTemperature":2700,"WhiteBrightness":25}';
      $p.=',{"id":21, "name":"Warmweiß 50%","Mode":2,"WhiteTemperature":2700,"WhiteBrightness":50}';
      $p.=',{"id":22, "name":"Kaltweiß 100%","Mode":2,"WhiteTemperature":6500,"WhiteBrightness":100}';
      $p.=',{"id":31, "name":"Disco","Mode":4,"DiscoProgram":1}';
      $p.=']';
      $this->RegisterPropertyString("Presets", $p);
   }

   public function Destroy() {
     //Never delete this line!
     parent::Destroy();
   }

   /* Änderungen des Optionsdialogs übernehmen */
   public function ApplyChanges() {
     //Never delete this line!
     parent::ApplyChanges();

      // Profil-Association für presets anlegen, je Instanz genau ein VProfil
      $ass = [];
      $presets = json_decode($this->ReadPropertyString("Presets"));
      foreach ($presets as $key => $preset) {
         $a = array($preset->id, $preset->name, "", -1);
         $ass[] = $a;
      }
      if ($ass!=[])
         $this->RegisterProfileIntegerAssociation("MilightV6.Preset".$this->InstanceID, "", "", "", $ass, 0);

      $this->RegisterProfileIntegerAssociation("MilightV6.Mode", "", "", "",
          [
           [self::MODE_OFF,"Aus","",-1],
           [self::MODE_COLOR,"Farbig","",-1],
           [self::MODE_WHITE,"Weiß","",-1],
           [self::MODE_NIGHT,"Nacht","",-1],
           [self::MODE_DISCO,"Disco","",-1]
        ], 0);
      $this->RegisterProfileInteger("MilightV6.Hue", "", "", "°", 0, 360, 18);
      $this->RegisterProfileInteger("MilightV6.Saturation", "", "", "%", 0, 100, 1);
      $this->RegisterProfileInteger("MilightV6.Brightness", "", "", "%", 0, 100, 1);
      $this->RegisterProfileInteger("MilightV6.Temperature", "", "", "K", 2700, 6500, 200);
      $this->RegisterProfileIntegerAssociation("MilightV6.TemperatureSet", "", "", "",
          [
           [2700,"Warmweiß","",-1],
           [4000,"Neutralweiß","",-1],
           [5000,"Morgensonne","",-1],
           [5700,"Mittagssonne","",-1],
           [6500,"Kaltweiß","",-1]
        ], 0);
      $this->RegisterProfileIntegerAssociation("MilightV6.DiscoProgram", "", "", "",
          [
           [0,"None","",-1],
           [1,"Color-Fade","",-1],
           [2,"White-Fade","",-1],
           [3,"RGB-Fade","",-1],
           [4,"Color-Flash","",-1],
           [5,"Color-Brightness-Flash","",-1],
           [6,"Red-Fade-Flash","",-1],
           [7,"Green-Fade-Flash","",-1],
           [8,"Blue-Fade-Flash","",-1],
           [9,"White-Fade-Flash","",-1]
        ], 0);
      $this->RegisterProfileIntegerAssociation("MilightV6.DiscoSpeed", "", "", "",
          [
           [0,"Langsamer","",-1],
           [1,"Schneller","",-1],
        ], 0);
      $this->RegisterProfileIntegerAssociation("MilightV6.Link", "", "", "",
          [
           [self::MODE_LINK,"Lampe anlernen","",-1],
           [self::MODE_UNLINK,"Lampe ablernen","",-1],
        ], 0);

      //Variablen erstellen
      $this->RegisterVariableInteger("Preset", "Vorgaben","MilightV6.Preset".$this->InstanceID,10);
      $this->RegisterVariableInteger("Mode", "Modus", "MilightV6.Mode",20);
      $this->RegisterVariableInteger("ColorHue", "Farbwert", "MilightV6.Hue",30);
      $this->RegisterVariableInteger("ColorSaturation", "Farbsättigung", "MilightV6.Saturation",31);
      $this->RegisterVariableInteger("ColorBrightness", "Farbhelligkeit","MilightV6.Brightness",32);
      $this->RegisterVariableInteger("Color", "Farbe", "~HexColor", 33);
      $this->RegisterVariableInteger("WhiteTemperature", "Farbtemperatur (weiß)", "MilightV6.Temperature",40);
      $this->RegisterVariableInteger("WhiteBrightness", "Helligkeit (weiß)","MilightV6.Brightness",41);
      $this->RegisterVariableInteger("DiscoProgram","Disco-Programm","MilightV6.DiscoProgram",50);
      $this->RegisterVariableInteger("DiscoSpeed","Disco-Geschwindigkeit","MilightV6.DiscoSpeed",51);

      $this->EnableAction("Mode");
      $this->EnableAction("ColorHue");
      $this->EnableAction("ColorSaturation");
      $this->EnableAction("ColorBrightness");
      $this->EnableAction("Color");
      $this->EnableAction("WhiteBrightness");
      $this->EnableAction("WhiteTemperature");
      $this->EnableAction("Preset");
      $this->EnableAction("DiscoProgram");
      $this->EnableAction("DiscoSpeed");

      // Vorbelegung der Variablen
      $this->SetValueInteger("Mode", 0 );
      $this->SetValueInteger("ColorHue", 0xFF );
      $this->SetValueInteger("ColorSaturation", 50 );
      $this->SetValueInteger("ColorBrightness", 100 );
      $this->SetValueInteger("WhiteBrightness", 100 );
      $this->SetValueInteger("WhiteTemperature", 2700 );
      $this->SetValueInteger("Preset", 10 );

      // Übernahme und Test der Werte
      $host = $this->ReadPropertyString("URL");
      $port = $this->ReadPropertyInteger("Port");
      $type = $this->ReadPropertyInteger("Type");
      $zone = $this->ReadPropertyInteger("Zone");

      $status = 102;
      if ( $host == "" ) $status = 201;
      if ( $port == 0 ) $status = 202;
      if ( ( $zone < self::ZONE_ALL )  or ( $zone > self::ZONE_4 ) ) $status = 203;
      if ( ( $type < self::TYPE_RGBW ) or ( $type > self::TYPE_RGBWW ) ) $status = 204;

      // Verbindung zum Gateway testen
      if ($status==102) {
         $socket = $this->createSocket($host, $port );
         if (is_resource($socket)) {
            if (!$this->getSessionID($socket)) {
               $this->Log("SessionID kann nicht ermittelt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
               $status = 205;
            }
            socket_close($socket);
         } else $status = 205;
      }

      $this->SetStatus( $status );
      return true;
   }

   /*
private function SetVisibility(integer $State)
	{
		switch ($State) {
		case 0: // aus
			$this->SetHidden('Color', true);
			$this->SetHidden('Brightness', true);
			break;
		case 1: // weiß
			$this->SetHidden('Color', true);
			$this->SetHidden('Brightness', false);
			break;
		case 2: // Farbe
			$this->SetHidden('Color', false);
			$this->SetHidden('Brightness', true);
			break;
		}
		$this->SetValueInteger('STATE', $State);
	}

   */

/* Update
   ---------------------------------------------------------
   Führt die gewünschte Aktion aus */
   private function Update() {
      $cmds=[];

      // setzen der Sichtbarkeit
      $mode = $this->GetValueInteger("Mode");
      $type = $this->ReadPropertyInteger("Type");
      $this->SetHidden("ColorHue",$mode!=SELF::MODE_COLOR);
      $this->SetHidden("ColorSaturation",($mode!=SELF::MODE_COLOR) or ($type!=self::TYPE_RGBWW) );
      $this->SetHidden("ColorBrightness",$mode!=SELF::MODE_COLOR);
      $this->SetHidden("Color",$mode!=SELF::MODE_COLOR);

      $this->SetHidden("WhiteTemperature",($mode!=SELF::MODE_WHITE) or ($type!=self::TYPE_RGBWW) );
      $this->SetHidden("WhiteBrightness",$mode!=SELF::MODE_WHITE);

      $this->SetHidden("DiscoProgram",$mode!=SELF::MODE_DISCO);
      $this->SetHidden("DiscoSpeed",$mode!=SELF::MODE_DISCO);

      // Anwenden der Änderungen
      $cmds = [];
      switch ($mode) {
         case SELF::MODE_OFF : //off
            $cmds[] = $this->getCmd(self::CMD_SWITCH_OFF);
            break;
         case SELF::MODE_COLOR : //farbig
            $cmds[] = $this->getCmd( self::CMD_SWITCH_ON );
            $cmds[] = $this->getCmd(self::CMD_SET_COLOR, $this->GetValueInteger("ColorHue"));
            $cmds[] = $this->getCmd(self::CMD_SET_SATURATION, $this->GetValueInteger("ColorSaturation"));
            $cmds[] = $this->getCmd(self::CMD_SET_BRIGHTNESS, $this->GetValueInteger("ColorBrightness"));
            break;
         case SELF::MODE_WHITE : //weiß
            $cmds[] = $this->getCmd(self::CMD_SWITCH_ON_WHITE);
            $cmds[] = $this->getCmd(self::CMD_SET_TEMPERATURE, $this->GetValueInteger("WhiteTemperature"));
            $cmds[] = $this->getCmd(self::CMD_SET_BRIGHTNESS, $this->GetValueInteger("WhiteBrightness"));
            break;
         case SELF::MODE_NIGHT : //Nacht
            $cmds[] = $this->getCmd(self::CMD_SWITCH_ON_NIGHT);
            break;
         case SELF::MODE_DISCO : //Disco
            $cmds[] = $this->getCmd(self::CMD_SET_DISCO_PROGRAM, $this->GetValueInteger("DiscoProgram"));
            break;
         case SELF::MODE_LINK :
            $cmds[] = $this->getCmd(self::CMD_SET_LINK_MODE);
            break;
         case SELF::MODE_UNLINK :
            $cmds[] = $this->getCmd(self::CMD_SET_UNLINK_MODE);
            break;

      }

      return $this->sendCmds( $cmds );
   }

   // Color aus HSB berechnen
   private function UpdateColor() {
      $rgb = $this->HSL2RGB( $this->GetValueInteger("ColorHue"),
                             $this->GetValueInteger("ColorSaturation"),
                             $this->GetValueInteger("ColorBrightness") );
      $this->SetValueInteger("Color", ($rgb[0] << 16) + ($rgb[1] << 8) + $rgb[2] );
   }

   public function SetMode(int $mode) {
     $this->SetValueInteger("Mode", $mode );
     return $this->Update();
   }

   public function SetColorHue(int $hue) {
     $hue = min(360,max(0,$hue));
     $this->SetValueInteger("ColorHue", $hue );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   public function SetColorSaturation(int $saturation) {
     $saturation = min(100,max(0,$saturation));
     $this->SetValueInteger("ColorSaturation", $saturation );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   public function SetColorBrightness(int $brightness) {
     $brightness = min(100,max(0,$brightness));
     $this->SetValueInteger("ColorBrightness", $brightness );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   public function SetColor(int $color) {
     $hsl = $this->RGB2HSV( $color >> 16, ($color & 0xFF00) >> 8, $color & 0xFF );
     $this->SetValueInteger("ColorHue", floor( $hsl[0] ) );
     $this->SetValueInteger("ColorSaturation", floor( $hsl[1] ) );
     $this->SetValueInteger("ColorBrightness", floor( $hsl[2] ) );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
   }

   public function SetWhiteBrightness(int $brightness) {
     $brightness = min(100,max(0,$brightness));
     $this->SetValueInteger("WhiteBrightness", $brightness );
     if ($this->GetValueInteger("Mode")==self::MODE_WHITE)
        return $this->Update();
     else return true;
   }

   public function SetWhiteTemperature(int $temperatur) {
	  $temperatur = min( 6500, max( 2700, $temperatur) );
     $this->SetValueInteger("WhiteTemperature", $temperatur );
     if ($this->GetValueInteger("Mode")==self::MODE_WHITE)
        return $this->Update();
     else return true;
   }

   public function SetPreset(int $presetid) {
     $this->SetValueInteger("Preset", $presetid );
     $presets = json_decode($this->ReadPropertyString("Presets"));
     foreach ($presets as $key => $preset) {
        if (isset($preset->id) and ($preset->id == $presetid)) {
           if (isset($preset->Mode)) $this->SetValueInteger("Mode", $preset->Mode );
           if (isset($preset->ColorHue)) $this->SetValueInteger("ColorHue", $preset->ColorHue );
           if (isset($preset->ColorSaturation)) $this->SetValueInteger("ColorSaturation", $preset->ColorSaturation );
           if (isset($preset->ColorBrightness)) $this->SetValueInteger("ColorBrightness", $preset->ColorBrightness );
           if (isset($preset->WhiteTemperature)) $this->SetValueInteger("WhiteTemperature", $preset->WhiteTemperature );
           if (isset($preset->WhiteBrightness)) $this->SetValueInteger("WhiteBrightness", $preset->WhiteBrightness );
           if (isset($preset->DiscoProgram)) $this->SetValueInteger("DiscoProgram", $preset->DiscoProgram );
        }
      }
     $this->UpdateColor();
     return $this->Update();
   }

   public function SetDiscoProgram(int $DiscoProgram) {
     $this->SetValueInteger("DiscoProgram", $DiscoProgram );
     if ($this->GetValueInteger("Mode")==self::MODE_DISCO)
        return $this->Update();
     else return true;
   }

   public function SetDiscoSpeed(int $DiscoSpeed) {
      $this->SetValueInteger("DiscoSpeed", $DiscoSpeed );
      if ($this->GetValueInteger("Mode")==self::MODE_DISCO) {
         $cmds = [];
         if ($DiscoSpeed==0) $cmds[] = $this->getCmd(self::CMD_DEC_DISCO_SPEED);
         else $cmds[] = $this->getCmd(self::CMD_INC_DISCO_SPEED);
         return $this->sendCmds( $cmds );
      }
      else return true;
   }

   public function RequestAction($Ident, $Value) {
     $this->Log($Value);
     switch($Ident) {
       case "Mode":
          $this->SetMode( $Value );
          break;
       case "ColorHue":
         $this->SetColorHue( $Value );
         break;
       case "ColorSaturation":
         $this->SetColorSaturation( $Value );
         break;
       case "ColorBrightness":
         $this->SetColorBrightness( $Value );
         break;
       case "Color":
         $this->SetColor( $Value );
         break;
       case "WhiteBrightness":
         $this->SetWhiteBrightness( $Value );
         break;
       case "WhiteTemperature":
         $this->SetWhiteTemperature( $Value );
         break;
       case "Preset":
         $this->SetPreset( $Value );
         break;
       case "DiscoProgram":
         $this->SetDiscoProgram( $Value );
         break;
       case "DiscoSpeed":
         $this->SetDiscoSpeed( $Value );
         break;

       default:
         throw new Exception("Invalid ident");
         }
   }

/* ================================================================
   IPS Helper, Setter, Getter
   ================================================================*/
   protected function SetHidden($Ident, $value) {
		$id = $this->GetIDForIdent($Ident); IPS_SetHidden($id, $value);
	}

   private function SetValueInteger($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueInteger($id) <> $value) { SetValueInteger($id, $value); return true; }
     return false;
   }
   private function GetValueInteger($Ident) {
     $id = $this->GetIDForIdent($Ident); return GetValueInteger($id);
   }

   private function SetValueBoolean($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueBoolean($id) <> $value) { SetValueBoolean($id, boolval($value));  return true; }
     return false;
   }
   private function GetValueBoolean($Ident) {
     $id = $this->GetIDForIdent($Ident); return GetValueBoolean($id);
   }

   private function SetValueFloat($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueFloat($id) <> $value) { SetValueFloat($id, $value); return true; }
     return false;
   }

   private function SetValueString($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if (GetValueString($id) <> $value) { SetValueString($id, $value); return true; }
     return false;
   }
   private function GetValueString($Ident) {
     $id = $this->GetIDForIdent($Ident); return GetValueString($id);
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
         $MinValue = 0; $MaxValue = 0;
      } else {
         $MinValue = $Associations[0][0]; $MaxValue = $Associations[sizeof($Associations)-1][0];
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
         $MinValue = 0; $MaxValue = 0;
      } else {
         $MinValue = $Associations[0][0]; $MaxValue = $Associations[sizeof($Associations)-1][0];
      }

      $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize);

      foreach($Associations as $Association) {
         IPS_SetVariableProfileAssociation($Name, boolval($Association[0]), $Association[1], $Association[2], $Association[3]);
      }
   }

/* ================================================================
   private functions
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
            IPS_LogMessage(__CLASS__, "[".$function."] " . $msg );
	      if (self::LOG_ECHO & $this->LogLevel)
	         echo __CLASS__."[".$function."] " . $msg ."\n";
	   }
  }

   // (r[255],g[255],b[255]) -> (h[360.0],s[100.0],l[100.0])
   private function RGB2HSV($R, $G, $B) {
      $R = $R / 255; $G = $G / 255; $B = $B / 255;

      $maxRGB = max($R, $G, $B);
      $minRGB = min($R, $G, $B);
      $chroma = $maxRGB - $minRGB;

      $V = 100 * $maxRGB;
      if ($chroma == 0) { $H = $S = 0; }
	   else {
         $S = 100 * ($chroma / $maxRGB);
         if ($R == $minRGB) $H = 3 - (($G - $B) / $chroma);
         elseif ($B == $minRGB) $H = 1 - (($R - $G) / $chroma);
         else $H = 5 - (($B - $R) / $chroma); // $G == $minRGB
         $H = 60 * $H;
	   }
      return array($H, $S, $V);
   }

   // (h[360.0],s[100.0],l[100.0]) -> (r[255],g[255],b[255])
   private function HSL2RGB($H,$S,$V) {
      $S = $S / 100; $V = $V / 100; $H=$H / 360;
      $H *= 6;
      $I = floor($H);
      $F = $H - $I;
      $M = $V * (1 - $S);
      $N = $V * (1 - $S * $F);
      $K = $V * (1 - $S * (1 - $F));
      switch ($I) {
        case 0:  $R=$V; $G=$K; $B=$M; break;
        case 1:  $R=$N; $G=$V; $B=$M; break;
        case 2:  $R=$M; $G=$V; $B=$K; break;
        case 3:  $R=$M; $G=$N; $B=$V; break;
        case 4:  $R=$K; $G=$M; $B=$V; break;
        default: $R=$V; $G=$M; $B=$N; break;
      }
      $R=floor($R*255); $G=floor($G*255); $B=floor($B*255);
      return array($R,$G,$B);
   }

   private function receiveString($socket) {
      $res = "";

      $receiveRetry = 1;
      while ($receiveRetry <= $this->receiveRetries) {
         //$this->Log("Empfangsversuch: $receiveRetry / $this->receiveRetries");
         $receiveBytes = socket_recv($socket, $buf, 128, 0); // MSG_DONTWAIT = 0x40
         if ($receiveBytes > 0 ) {
            $res = $buf;
            break;
         } else {
            IPS_Sleep(250);
            $receiveRetry++;
         }
   }
//$this->Log(chunk_split(bin2hex( $res ),2," "));
   return $res;
 }

   private function sendString($socket, $buf) {

      $sendRetry=1;
	   $sentBytes=0;
//$this->Log(chunk_split(bin2hex( $buf ),2," "));

      while ( ($sendRetry <= $this->sendRetries) and ($sentBytes==0) ) {
         //$this->Log("Sendeversuch: .$sendRetry / $this->sendRetries");
         $sentBytes = socket_send($socket, $buf, strlen($buf), 0);
         $sendRetry++;
      }
   return $sentBytes;
   }

   private function sendByteArray($socket, array $bytes) {
      $buf = vsprintf(str_repeat('%c', count($bytes)), $bytes);
      return $this->sendString($socket, $buf);
   }

   private function getSessionID($socket) {
      $sentBytes = $this->sendByteArray($socket, self::$CMD_GetSessionID );
		if ($sentBytes!=0) {
		   IPS_Sleep(100);
         $receive = $this->receiveString($socket);
         if (strlen($receive) > 20) {
            $this->MACAdr = substr(chunk_split(bin2hex( substr($receive,8,6) ),2,":"),0,-1);
            $this->SessionID1 = ord($receive[19]);
            $this->SessionID2 = ord($receive[20]);
            //$this->Log("MAC= $this->MACAdr ID1=$this->SessionID1");
            return true;
         }
      }
      return false;
   }

   /*    */
   private function createSocket( $host, $port ) {
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

      if ($host=="255.255.255.255")
         if (!socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1)) {
            $this->Log("Socket-Option SO_BROADCAST kann nicht gesetzt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
            socket_close($socket);
            return false;
   		};

		$result = socket_connect($socket, $host, $port);
      if ($result === false) {
         $this->Log("Socket kann nicht mit $host:$port verbunden werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
      }
//$this->Log("Socket wurde erfolgreich erzeugt $host:$port");
      return $socket;
   }

   /* */
   private function detectBridges() {
      //$socket = $this->createSocket($this->ReadPropertyString("URL"), 48899 );
      $socket = $this->createSocket("255.255.255.255", 48899 );
      if (!is_resource($socket)) return false;

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


   /* sendCmds
     Öffnet einen UDP Socket und sendet die Daten in cmds
   */
   private function sendCmds( $cmds ) {
      $socket = $this->createSocket($this->ReadPropertyString("URL"), $this->ReadPropertyInteger("Port") );
      if (!is_resource($socket)) return false;

      if (!$this->getSessionID($socket)) {
         $this->Log("SessionID kann nicht ermittelt werden.".socket_strerror(socket_last_error()),self::LOG_ERRORS);
         socket_close($socket);
         return false;
      }

      // alle cmds nacheinander senden
      foreach ($cmds as $key => $cmd) if ($cmd<>[]) {
//$this->Log( $key.": ".chunk_split( bin2hex( vsprintf(str_repeat('%c', count($cmd)), $cmd)), 2, ' '));
//break;

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

         $sentBytes = $this->sendByteArray($socket, $bytes);
		   if ($sentBytes==0) {
	         $msg = chunk_split(bin2hex( vsprintf(str_repeat('%c', count($bytes)), $bytes), 2, ' '));
            $this->Log("Sendefehler: send=$msg",self::LOG_ERRORS);
            socket_close($socket);
            return false;
         }
         $this->SequenceNbr = ($this->SequenceNbr + 1) & 0xFF;

	      IPS_Sleep(100);
         $receive = $this->receiveString($socket);
         if ( (strlen($receive) < 8) or ($receive[7]!=0) ){
	         $msg = chunk_split(bin2hex( vsprintf(str_repeat('%c', count($bytes)), $bytes), 2, ' '));
            $this->Log("Empfangsfehler: send=$msg",self::LOG_ERRORS);
            socket_close($socket);
            return false;
	      }
	      IPS_Sleep(100);

      }
     socket_close($socket);
     return true;
   }


   // liefert zum Befehl cmd das zu sendende ByteArray zurück
   private function getCmd( $cmd, $value=0 ) {
      $result=[];
      $type = $this->ReadPropertyInteger("Type");

      if (!(array_key_exists ($type, self::$CMDS ))) {
         $this->Log("Fehler: Lampen-Type=$type ist unbekannt ",self::LOG_ERRORS);
         return $result ;
      }
      if (!(array_key_exists ($cmd, self::$CMDS[$type] ))) {
         $this->Log("Fehler: Lampen-Befehl=$cmd ist unbekannt ",self::LOG_ERRORS);
         return $result;
      }

      $result=self::$CMDS[$type][$cmd];

      // muss im result noch was gepatched werden ?
      $patch=[];
      switch ($cmd) {
         case self::CMD_SET_COLOR:
            $value = intval( min( 360, max( 0, $value) ) / 360 * 255);
            $patch = array(5=>$value, 6=>$value, 7=>$value, 8=>$value );
            break;
         case self::CMD_SET_BRIGHTNESS:
            $value = min( 100, max( 0, $value) );
            $patch = array(5=>$value );
            break;
         case self::CMD_SET_SATURATION:
            $value = 100 - min( 100, max( 0, $value) );
            $patch = array(5=>$value );
            break;
         case self::CMD_SET_TEMPERATURE:
            $value = intval( ( min( 6500, max( 2700, $value) ) - 2700 ) / (6500-2700) * 0x64);
            $patch = array(5=>$value );
            break;
         case self::CMD_SET_DISCO_PROGRAM:
            $value = intval( min( 9, max( 0, $value) ) );
            $patch = array(5=>$value );
            break;
      }
      foreach ($patch as $key => $value) {
         $result[$key]=$value;
      }

      // patch zone (nicht bei BRIDGE)
      if ( ($type!=self::TYPE_BRIDGE) and (count($result)>9) ) {
         $zone = $this->ReadPropertyInteger("Zone");
         $result[9] = $zone;
      }

      return $result;
   }


}


?>
