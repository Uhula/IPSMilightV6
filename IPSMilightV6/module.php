<?

/*  IPS Modul Milight V6
    ---------------------------------------------------------------------------
    Modul zum Ansteuern der Milight-Gateways iBox2 mit der Treiberversion 6,
    welche auch RGBWW Lampen unterstützt.

   Info:  https://github.com/Uhula/IPSMilightV6.git
   (c) 2017 Uhula, use on your own risc


H: linear-gradient(to right, rgba(255,0,0,1) 0%,rgba(255,255,0,1) 16%,rgba(1,255,1,1) 33%,rgba(0,255,255,1) 50%,rgba(0,0,255,1) 66%,rgba(255,0,255,1) 84%,rgba(255,0,4,1) 100%)
S: linear-gradient(to right, rgba(128,128,128,1) 0%,rgba(255,0,0,1) 100%);
B: linear-gradient(to right, rgba(0,0,0,1) 0%,rgba(255,255,255,1) 100%);

R:
G:
B: linear-gradient(to right, rgba(255,255,255,1) 0%,rgba(0,0,255,1) 100%);

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

   const COLORAS_ALL = 0;
   const COLORAS_HSB = 1;
   const COLORAS_RGB = 2;
   const COLORAS_WHEEL = 3;

   const MODE_OFF   = 0x00;
   const MODE_COLOR = 0x01;
   const MODE_WHITE = 0x02;
   const MODE_NIGHT = 0x04;
   const MODE_DISCO = 0x08;
   const MODE_LINK  = 0x10;
   const MODE_UNLINK= 0x20;

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
      $this->RegisterPropertyBoolean("ConfigAsPopup", false);
      $this->RegisterPropertyInteger("ColorAs", self::COLORAS_HSB);
      $this->RegisterPropertyInteger("AllowedModes", self::MODE_COLOR | self::MODE_WHITE );
      $p ='[';
      $p.='{"ID":0, "Name":"Rot","Mode":1,"ColorH":0,"ColorS":100,"ColorV":100}';
      $p.=',{"ID":1, "Name":"Grün 75%","Mode":1,"ColorH":120,"ColorS":100,"ColorV":75}';
      $p.=',{"ID":2, "Name":"Blau Sätt.50%","Mode":1,"ColorH":240,"ColorS":50,"ColorV":100}';
      $p.=',{"ID":3, "Name":"Warmweiß 25%","Mode":2,"WhiteT":2700,"WhiteV":25}';
      $p.=',{"ID":4, "Name":"Kaltweiß 100%","Mode":2,"WhiteT":6500,"WhiteV":100}';
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
      // properties auslesen, werden später benötigt
      $host = $this->ReadPropertyString("URL");
      $port = $this->ReadPropertyInteger("Port");
      $type = $this->ReadPropertyInteger("Type");
      $zone = $this->ReadPropertyInteger("Zone");
      $Modes = $this->ReadPropertyInteger("AllowedModes");
      $ColorAs = $this->ReadPropertyInteger("ColorAs");


      if (IPS_GetName($this->InstanceID)=="MiLightV6")
        IPS_SetName($this->InstanceID, "MiLightV6 ".$this->ReadPropertyInteger("Type").":".$this->ReadPropertyInteger("Zone"));

      // Profil-Association für presets anlegen, je Instanz genau ein VProfil
      $ass = [];
      $presets = json_decode($this->ReadPropertyString("Presets"));
      if ($presets and ($presets!=[]))
         foreach ($presets as $key => $preset) {
            $a = array($preset->ID, $preset->Name, "", -1);
            $ass[] = $a;
         }
      if (IPS_VariableProfileExists("MilightV6.Preset".$this->InstanceID))
     	   IPS_DeleteVariableProfile("MilightV6.Preset".$this->InstanceID);
      if ($ass!=[]) {
         $this->RegisterProfileIntegerAssociation("MilightV6.Preset".$this->InstanceID, "", "", "", $ass, 0);
      }

      // Modes
      $ass = [];
      $ass[] = array(self::MODE_OFF,"Aus","",-1);
      if ($Modes & self::MODE_COLOR) $ass[] = array(self::MODE_COLOR,"Farbig","",-1);
      if ($Modes & self::MODE_WHITE) $ass[] = array(self::MODE_WHITE,"Weiß","",-1);
      if ($Modes & self::MODE_NIGHT) $ass[] = array(self::MODE_NIGHT,"Nacht","",-1);
      if ($Modes & self::MODE_DISCO) $ass[] = array(self::MODE_DISCO,"Disco","",-1);
      if ($Modes & self::MODE_LINK) $ass[] = array(self::MODE_LINK,"Anlernen","",-1);
      if ($Modes & self::MODE_UNLINK) $ass[] = array(self::MODE_UNLINK,"Ablernen","",-1);
      if (IPS_VariableProfileExists("MilightV6.Mode".$this->InstanceID))
     	   IPS_DeleteVariableProfile("MilightV6.Mode".$this->InstanceID);
      $this->RegisterProfileIntegerAssociation("MilightV6.Mode".$this->InstanceID, "", "", "", $ass, 0);

      $this->RegisterProfileInteger("MilightV6.360", "", "", "%", 0, 360, 1);
      $this->RegisterProfileInteger("MilightV6.100", "", "", "%", 0, 100, 1);
      $this->RegisterProfileInteger("MilightV6.255", "", "", "%", 0, 255, 1);
      $this->RegisterProfileInteger("MilightV6.ColorTemp", "", "", "%", 2700, 6500, 1);
      $this->RegisterProfileIntegerAssociation("MilightV6.ColorTempSet", "", "", "",
          [
           [2700,"Warmweiß","",-1],
           [4000,"Neutralweiß","",-1],
           [5000,"Morgensonne","",-1],
           [5700,"Mittagssonne","",-1],
           [6500,"Kaltweiß","",-1]
        ], 0);
      $this->RegisterProfileIntegerAssociation("MilightV6.DiscoProgram", "", "", "",
          [
           [0,"Ohne","",-1],
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

      //Variablen deklarieren, werden für die Instanziierung und evtl die Links im Popup benötigt
      $RegVars = [];
      $RegVars[] = ["Type" => "int", "Name" => "Mode", "Bez" => "Zustand", "Profil" => "MilightV6.Mode".$this->InstanceID, "Link"=>true, "Action"=>true, "ID" => 0];
      $RegVars[] = ["Type" => "int", "Name" => "PresetID", "Bez" => "Lichtvorlage", "Profil" => "MilightV6.Preset".$this->InstanceID, "Link"=>false, "Action"=>true, "ID" => 0];
      $RegVars[] = ["Type" => "group", "Bez" => "Farb-Einstellungen", "Link"=>true];
      $RegVars[] = ["Type" => "int", "Name" => "ColorH", "Bez" => "Farbwert", "Profil" => "MilightV6.360", "Link"=>($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_HSB), "ID" => 0, "Action"=>true, "Icon" => "slider.type=hue;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "ColorS", "Bez" => "Farbsättigung", "Profil" => "MilightV6.100", "Link"=>(($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_HSB)) and ($type==self::TYPE_RGBWW), "ID" => 0, "Action"=>true, "Icon" => "slider.type=saturation;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "ColorV", "Bez" => "Farbhelligkeit", "Profil" => "MilightV6.100", "Link"=>($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_HSB), "ID" => 0, "Action"=>true, "Icon" => "slider.type=brightness;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "ColorR", "Bez" => "Rotwert", "Profil" => "MilightV6.255", "Link"=>($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_RGB), "ID" => 0, "Action"=>true, "Icon" => "slider.type=red;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "ColorG", "Bez" => "Grünwert", "Profil" => "MilightV6.255", "Link"=>($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_RGB), "ID" => 0, "Action"=>true, "Icon" => "slider.type=green;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "ColorB", "Bez" => "Blauwert", "Profil" => "MilightV6.255", "Link"=>($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_RGB), "ID" => 0, "Action"=>true, "Icon" => "slider.type=blue;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "Color", "Bez" => "Farbe", "Profil" => "~HexColor", "Link"=>($ColorAs==self::COLORAS_ALL) or ($ColorAs==self::COLORAS_WHEEL), "Action"=>true, "ID" => 0];
      $RegVars[] = ["Type" => "group", "Bez" => "Weiß-Einstellungen", "Link"=>true];
      $RegVars[] = ["Type" => "int", "Name" => "WhiteT", "Bez" => "Farbtemperatur (weiß)", "Profil" => "MilightV6.ColorTemp", "Link"=>($type==self::TYPE_RGBWW), "ID" => 0, "Action"=>true, "Icon" => "slider.type=colortemp;slider.text=hide"];
      $RegVars[] = ["Type" => "int", "Name" => "WhiteV", "Bez" => "Helligkeit (weiß)", "Profil" => "MilightV6.100", "Link"=>true, "ID" => 0, "Action"=>true, "Icon" => "slider.type=brightness;slider.text=hide"];
      $RegVars[] = ["Type" => "group", "Bez" => "Disco-Einstellungen", "Link"=>true];
      $RegVars[] = ["Type" => "int", "Name" => "DiscoProgram", "Bez" => "Disco-Programm", "Profil" => "MilightV6.DiscoProgram", "Link"=>true, "Action"=>true, "ID" => 0];
      $RegVars[] = ["Type" => "int", "Name" => "DiscoSpeed", "Bez" => "Disco-Geschwindigkeit", "Profil" => "MilightV6.DiscoSpeed", "Link"=>true, "ID" => 0, "Action"=>true, "Icon" => "ele.style=btn"];
      foreach ($RegVars as $key => $v) {
         switch ($v["Type"]) {
            case "int" :
               $RegVars[$key]["ID"] = $this->RegisterVariableInteger($v["Name"], $v["Bez"],$v["Profil"],$key);
               break;
            case "string" :
               $RegVars[$key]["ID"] = $this->RegisterVariableString($v["Name"], $v["Bez"],$v["Profil"],$key);
               break;
            case "group" :
               break;
         }
         if (array_key_exists("ID",$RegVars[$key])) {
            if ($v["Action"]) $this->EnableAction($v["Name"]);
            if (array_key_exists ("Icon", $v))
               IPS_SetIcon ($RegVars[$key]["ID"], $v["Icon"] );
         }
      }

      // Vorbelegung der Variablen
      $this->SetValueInteger("Mode", 0 );
      $this->SetValueInteger("ColorH", 0xFF );
      $this->SetValueInteger("ColorS", 50 );
      $this->SetValueInteger("ColorV", 100 );
      $this->SetValueInteger("WhiteV", 100 );
      $this->SetValueInteger("WhiteT", 2700 );
      $this->UpdateColor();

      // wenn Einstellungen via Popup, dann Child-Instanzen erzeugen (vorher entfernen)
      $CatID = @IPS_GetCategoryIDByName("Lampeneinstellungen", $this->InstanceID);
      if ( $CatID )
         $this->DeleteCategory( $CatID );
      $ScriptID = @IPS_GetScriptIDByName("Lampeneinstellungen", $this->InstanceID);
      if ($ScriptID)
         IPS_DeleteScript ( $ScriptID, true );

      if ( ($CatID === false) and $this->ReadPropertyBoolean("ConfigAsPopup") ) {
         $CatID = IPS_CreateCategory();
         IPS_SetName($CatID, "Lampeneinstellungen");
         IPS_SetParent($CatID, $this->InstanceID);

        $GroupID = false;
         foreach ($RegVars as $key => $v) {
            if ($v["Link"]) {
               switch ($v["Type"]) {
                  case "group" :
                     // Dummy-Modul für die Gruppierung erzeugen
                     $GroupID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                     IPS_SetName($GroupID,  $v["Bez"]);
                     IPS_SetParent($GroupID, $CatID);
                     IPS_SetPosition ($GroupID, $key );
                     break;
                  default:
                     // Link erzeugen und in das Popup bzw die Gruppe setzen
                     $LinkID = IPS_CreateLink();
                     IPS_SetName($LinkID, $v["Bez"]);
                     if ($GroupID) IPS_SetParent($LinkID, $GroupID);
                     else IPS_SetParent($LinkID, $CatID);
                     IPS_SetLinkTargetID($LinkID, $v["ID"]);
                     IPS_SetPosition ($LinkID, $key );
                     break;
               }
            }
         }

         // Script zum Anzeigen der Konfiguration als Popup, geht erst ab IPS 4.1
         $ScriptID = IPS_CreateScript(0);
         IPS_SetParent($ScriptID, $this->InstanceID);
         IPS_SetName($ScriptID, "Lampeneinstellungen");
         IPS_SetScriptContent($ScriptID, '<? if ($_IPS["SENDER"]=="WebFront") WFC_OpenCategory($_IPS["CONFIGURATOR"], '.$CatID.');  ?>');
         IPS_SetIcon ($ScriptID, "icon=none" );
         IPS_SetPosition ($ScriptID, 9999 );
   }


      $status = 102; // ok
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
      $this->UpdateVisibility();

      return ($status==102);
   }

   private function UpdateVisibility() {
      $hide = $this->ReadPropertyBoolean("ConfigAsPopup");
      $mode = $this->GetValueInteger("Mode");
      $type = $this->ReadPropertyInteger("Type");
      $ColorAs = $this->ReadPropertyInteger("ColorAs");


      $this->SetHidden("ColorH",$hide or ($mode!=SELF::MODE_COLOR) or ( ($ColorAs!=self::COLORAS_ALL) and ($ColorAs!=self::COLORAS_HSB) ) );
      $this->SetHidden("ColorS",$hide or ($mode!=SELF::MODE_COLOR) or ($type!=self::TYPE_RGBWW) or ( ($ColorAs!=self::COLORAS_ALL) and ($ColorAs != self::COLORAS_HSB) ) );
      $this->SetHidden("ColorV",$hide or ($mode!=SELF::MODE_COLOR) or ( ($ColorAs!= self::COLORAS_ALL) and ($ColorAs!=self::COLORAS_HSB) ) );

      $this->SetHidden("ColorR",$hide or ($mode!=SELF::MODE_COLOR) or ( ($ColorAs!=self::COLORAS_ALL) and ($ColorAs!=self::COLORAS_RGB) ) );
      $this->SetHidden("ColorG",$hide or ($mode!=SELF::MODE_COLOR) or ( ($ColorAs!=self::COLORAS_ALL) and ($ColorAs!=self::COLORAS_RGB) ) );
      $this->SetHidden("ColorB",$hide or ($mode!=SELF::MODE_COLOR) or ( ($ColorAs!=self::COLORAS_ALL) and ($ColorAs!=self::COLORAS_RGB) ) );

      $this->SetHidden("Color",$hide or ($mode!=SELF::MODE_COLOR) or ( ($ColorAs!=self::COLORAS_ALL) and ($ColorAs!=self::COLORAS_WHEEL) ) );

      $this->SetHidden("WhiteT",$hide or ($mode!=SELF::MODE_WHITE) or ($type!=self::TYPE_RGBWW) );
      $this->SetHidden("WhiteV",$hide or ($mode!=SELF::MODE_WHITE));

      $this->SetHidden("DiscoProgram",$hide or ($mode!=SELF::MODE_DISCO));
      $this->SetHidden("DiscoSpeed",$hide or ($mode!=SELF::MODE_DISCO));

      $this->SetHidden("PresetID",$this->ReadPropertyString("Presets")=="");
}


/* Update
   ---------------------------------------------------------
   Führt die gewünschte Aktion aus */
   private function Update() {

      $this->UpdateVisibility();

      $cmds=[];
      $mode = $this->GetValueInteger("Mode");
      $type = $this->ReadPropertyInteger("Type");
      // Anwenden der Änderungen
      $cmds = [];
      switch ($mode) {
         case SELF::MODE_OFF : //off
            $cmds[] = $this->getCmd(self::CMD_SWITCH_OFF);
            break;
         case SELF::MODE_COLOR : //farbig
            $cmds[] = $this->getCmd( self::CMD_SWITCH_ON );
            $cmds[] = $this->getCmd(self::CMD_SET_COLOR, $this->GetValueInteger("ColorH"));
            $cmds[] = $this->getCmd(self::CMD_SET_SATURATION, $this->GetValueInteger("ColorS"));
            $cmds[] = $this->getCmd(self::CMD_SET_BRIGHTNESS, $this->GetValueInteger("ColorV"));
            break;
         case SELF::MODE_WHITE : //weiß
            $cmds[] = $this->getCmd(self::CMD_SWITCH_ON_WHITE);
            $cmds[] = $this->getCmd(self::CMD_SET_TEMPERATURE, $this->GetValueInteger("WhiteT"));
            $cmds[] = $this->getCmd(self::CMD_SET_BRIGHTNESS, $this->GetValueInteger("WhiteV"));
            break;
         case SELF::MODE_NIGHT : //Nacht
            $cmds[] = $this->getCmd(self::CMD_SWITCH_ON_NIGHT);
            break;
         case SELF::MODE_DISCO : //Disco
            $cmds[] = $this->getCmd(self::CMD_SET_DISCO_PROGRAM, $this->GetValueInteger("DiscoProgram"));
            break;
         case SELF::MODE_LINK :
            // Lampen sind nur 3 Sek im Lernmodus, also hier 10 Sek lang den Befehl sendende
            $cmds[] = $this->getCmd(self::CMD_SET_LINK_MODE);
            for ($i=0; $i<10; $i++) {
//               if ($i % 2 == 0)
//                  WFC_SendNotification ( 47626 /*[WebFront]*/, "Lampe anlernen", "Lampe einschalten\n Noch ".(10-$i)." Sekunden", "", 10-$i );
               $this->sendCmds( $cmds );
               IPS_Sleep( 1000 );
            }
            break;
         case SELF::MODE_UNLINK :
            // Lampen sind nur 3 Sek im Lernmodus, also hier 10 Sek lang den Befehl sendende
            $cmds[] = $this->getCmd(self::CMD_SET_UNLINK_MODE);
            for ($i=0; $i<10; $i++) {
               $this->sendCmds( $cmds );
               IPS_Sleep( 1000 );
            }
            break;
         //case SELF::MODE_OPTIONS :
            //IPS_RunScript ( IPS_GetScriptIDByName  ( "Lampeneinstellungen", $this->InstanceID ) );
            //IPS_RunScriptTextEx('echo $_IPS["Anfang"] . " ".  $_IPS["Ende"];', Array("Anfang" => "Hallo", "Ende" => "Welt"));
            //break;

      }
      if ($cmds!=[])
         return $this->sendCmds( $cmds );
   }

   // Color aus HSB berechnen
   private function UpdateColor() {
      $rgb = $this->HSL2RGB( $this->GetValueInteger("ColorH"),
                             $this->GetValueInteger("ColorS"),
                             $this->GetValueInteger("ColorV") );
      $this->SetValueInteger("Color", ($rgb[0] << 16) + ($rgb[1] << 8) + $rgb[2] );
      $this->SetValueInteger("ColorR", $rgb[0] );
      $this->SetValueInteger("ColorG", $rgb[1] );
      $this->SetValueInteger("ColorB", $rgb[2] );
   }

   public function SetMode(int $mode) {
     $this->SetValueInteger("Mode", $mode );
     return $this->Update();
   }

   public function SetColorH(int $hue) {
     $hue = min(360,max(0,$hue));
     $this->SetValueInteger("ColorH", $hue );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   public function SetColorS(int $saturation) {
     $saturation = min(100,max(0,$saturation));
     $this->SetValueInteger("ColorS", $saturation );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   public function SetColorV(int $brightness) {
     $brightness = min(100,max(0,$brightness));
     $this->SetValueInteger("ColorV", $brightness );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   private function SetHSVByRGB( $r, $b, $g ) {
     $hsv = $this->RGB2HSV( $r, $b, $g );
     $this->SetValueInteger("ColorH", floor( $hsv[0] ) );
     $this->SetValueInteger("ColorS", floor( $hsv[1] ) );
     $this->SetValueInteger("ColorV", floor( $hsv[2] ) );
     $this->UpdateColor();
     if ($this->GetValueInteger("Mode")==self::MODE_COLOR)
        return $this->Update();
     else return true;
   }

   public function SetColorR(int $value ) {
     $value = min(255,max(0,$value));
     $color = $this->GetValueInteger("Color");
     return $this->SetHSVByRGB( $value, ($color & 0xFF00) >> 8, $color & 0xFF );
   }

   public function SetColorG(int $value ) {
     $value = min(255,max(0,$value));
     $color = $this->GetValueInteger("Color");
     return $this->SetHSVByRGB( $color >> 16, $value, $color & 0xFF );
   }

   public function SetColorB(int $value ) {
     $value = min(255,max(0,$value));
     $color = $this->GetValueInteger("Color");
     return $this->SetHSVByRGB( $color >> 16, ($color & 0xFF00) >> 8, $value );
   }

   public function SetColor(int $color) {
     return $this->SetHSVByRGB( $color >> 16, ($color & 0xFF00) >> 8, $color & 0xFF );
   }

   public function SetWhiteV(int $value) {
     $value = min(100,max(0,$value));
     $this->SetValueInteger("WhiteV", $value );
     if ($this->GetValueInteger("Mode")==self::MODE_WHITE)
        return $this->Update();
     else return true;
   }

   public function SetWhiteT(int $value) {
	  $value = min( 6500, max( 2700, $value) );
     $this->SetValueInteger("WhiteT", $value );
     if ($this->GetValueInteger("Mode")==self::MODE_WHITE)
        return $this->Update();
     else return true;
   }

   public function SetPresetID(int $presetid) {
     $this->SetValueInteger("PresetID", $presetid );
     $presets = json_decode($this->ReadPropertyString("Presets"));
     foreach ($presets as $key => $preset) {
        if (isset($preset->ID) and ($preset->ID == $presetid)) {
           if (isset($preset->Mode)) $this->SetValueInteger("Mode", $preset->Mode );
           if (isset($preset->ColorH)) $this->SetValueInteger("ColorH", $preset->ColorH );
           if (isset($preset->ColorS)) $this->SetValueInteger("ColorS", $preset->ColorS );
           if (isset($preset->ColorV)) $this->SetValueInteger("ColorV", $preset->ColorV );
           if (isset($preset->ColorR)) $this->SetValueInteger("ColorR", $preset->ColorR );
           if (isset($preset->ColorG)) $this->SetValueInteger("ColorG", $preset->ColorG );
           if (isset($preset->ColorB)) $this->SetValueInteger("ColorB", $preset->ColorB );
           if (isset($preset->Color)) $this->SetValueInteger("Color", $preset->Color );
           if (isset($preset->WhiteT)) $this->SetValueInteger("WhiteT", $preset->WhiteT );
           if (isset($preset->WhiteV)) $this->SetValueInteger("WhiteV", $preset->WhiteV );
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
     switch($Ident) {
       case "Mode": $this->SetMode( $Value ); break;
       case "ColorH": $this->SetColorH( $Value ); break;
       case "ColorS": $this->SetColorS( $Value ); break;
       case "ColorV": $this->SetColorV( $Value ); break;
       case "ColorR": $this->SetColorR( $Value ); break;
       case "ColorG": $this->SetColorG( $Value ); break;
       case "ColorB": $this->SetColorB( $Value ); break;
       case "Color":  $this->SetColor( $Value ); break;
       case "WhiteV": $this->SetWhiteV( $Value ); break;
       case "WhiteT": $this->SetWhiteT( $Value ); break;
       case "PresetID": $this->SetPresetID( $Value ); break;
       case "DiscoProgram": $this->SetDiscoProgram( $Value ); break;
       case "DiscoSpeed": $this->SetDiscoSpeed( $Value ); break;

       default:
         throw new Exception("Invalid ident");
         }
   }

/* ================================================================
   IPS Helper, Setter, Getter
   ================================================================*/
   // Löschen einer Kategory inklusve Inhalt
   private function DeleteCategory($CategoryId) {
      $this->EmptyCategory($CategoryId);
      IPS_DeleteCategory($CategoryId);
    }

   // Löschen eines beliebigen Objektes
   private function DeleteObject($ObjectId) {
      $Object     = IPS_GetObject($ObjectId);
      $ObjectType = $Object['ObjectType'];
      switch ($ObjectType) {
         case 0: DeleteCategory($ObjectId); break;
         case 1: $this->EmptyCategory($ObjectId);  IPS_DeleteInstance($ObjectId); break;
         case 2: IPS_DeleteVariable($ObjectId); break;
         case 3: IPS_DeleteScript($ObjectId, false);  break;
         case 4: IPS_DeleteEvent($ObjectId);  break;
         case 5: IPS_DeleteMedia($ObjectId, true); break;
         case 6: IPS_DeleteLink($ObjectId);  break;
      }
   }


   // Löschen des Inhalts einer Kategorie inklusve Inhalt
   private function EmptyCategory($CategoryId) {
      if ($CategoryId==0) return false;
      $ChildrenIds = IPS_GetChildrenIDs($CategoryId);
      foreach ($ChildrenIds as $ObjectId)
         $this->DeleteObject($ObjectId);
   }

   protected function SetHidden($Ident, $value) {
		$id = $this->GetIDForIdent($Ident);
      if ($id) IPS_SetHidden($id, $value);
	}

   private function SetValueInteger($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if ( $id and (GetValueInteger($id) <> $value)) { SetValueInteger($id, $value); return true; }
     return false;
   }
   private function GetValueInteger($Ident) {
     $id = $this->GetIDForIdent($Ident);
     if ($id) return GetValueInteger($id);
   }

   private function SetValueBoolean($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if ( $id and (GetValueBoolean($id) <> $value) ) { SetValueBoolean($id, boolval($value));  return true; }
     return false;
   }
   private function GetValueBoolean($Ident) {
     $id = $this->GetIDForIdent($Ident);
     if ($id) return GetValueBoolean($id);
   }

   private function SetValueFloat($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if ( $id and (GetValueFloat($id) <> $value) ) { SetValueFloat($id, $value); return true; }
     return false;
   }

   private function GetValueString($Ident) {
     $id = $this->GetIDForIdent($Ident);
     if ($id) return GetValueString($id);
   }

   private function SetValueString($Ident, $value) {
     $id = $this->GetIDForIdent($Ident);
     if ( $id and (GetValueString($id) <> $value) ) { SetValueString($id, $value); return true; }
     return false;
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
         $receiveBytes = @socket_recv($socket, $buf, 128, 0); // MSG_DONTWAIT = 0x40
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
         $sentBytes = @socket_send($socket, $buf, strlen($buf), 0);
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
