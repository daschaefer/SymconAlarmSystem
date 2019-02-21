<?

class AlarmSystem extends IPSModule
{
         
    public function Create() {
        parent::Create();

        $this->RegisterPropertyInteger("Notification_Push", false);
        $this->RegisterPropertyString("TriggerList", ""); 
        $this->RegisterPropertyInteger("TriggerDelay", 0); 
        $this->RegisterPropertyInteger("WFC", 0); 
        $this->RegisterPropertyInteger("PreArmScript", 0); 
        $this->RegisterPropertyInteger("PreDisarmScript", 0); 
        $this->RegisterPropertyInteger("PreDisableScript", 0);
        $this->RegisterPropertyInteger("KeepProtocolForDays", 7);
    }
    
    public function ApplyChanges() {
        parent::ApplyChanges();

        // Get Handler for Archive
        $instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}'); 
        $archive_handler = $instances[0];
        

        // Create AlarmController script
        $alarmControllerScriptID = @$this->GetIDForIdent("AlarmController");
        if($alarmControllerScriptID === false) {
            $alarmControllerScriptID = $this->RegisterScript("AlarmController", "AlarmController", file_get_contents(__DIR__ . "/AlarmController.php"), 100);
        } else {
            IPS_SetScriptContent($alarmControllerScriptID, file_get_contents(__DIR__ . "/AlarmController.php"));
        }
        IPS_SetHidden($alarmControllerScriptID, true);

        // Create ShortenProtocol script
        $scriptID = @$this->GetIDForIdent("ShortenProtocol");
        if($scriptID === false) {
            $scriptID = $this->RegisterScript("ShortenProtocol", "ShortenProtocol", file_get_contents(__DIR__ . "/ShortenProtocol.php"), 100);
            $eid = IPS_CreateEvent(1);
            IPS_SetParent($eid, $scriptID);
            IPS_SetEventCyclicTimeFrom($eid, 0, 0, 0);
            IPS_SetEventActive($eid, true);
        } else {
            IPS_SetScriptContent($scriptID, file_get_contents(__DIR__ . "/ShortenProtocol.php"));
        }
        IPS_SetHidden($scriptID, true);

        // Variables
        // Alarmprotokoll
        $var = @IPS_GetObjectIDByIdent("EventList", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetIdent($var, "EventList");
            IPS_SetParent($var, $this->InstanceID);
            AC_SetLoggingStatus($archive_handler, $var, true);
            AC_SetAggregationType($archive_handler, $var, 0);
            IPS_ApplyChanges($archive_handler);
            IPS_SetName($var, "Protokoll");
        }

        $var = @IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetIdent($var, "EventListHTML");
            IPS_SetParent($var, $this->InstanceID);
            IPS_SetName($var, "HTML Protokoll");
            IPS_SetVariableCustomProfile($var, "~HTMLBox");
        }

        $var = @IPS_GetObjectIDByIdent("EventListJSON", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetIdent($var, "EventListJSON");
            IPS_SetParent($var, $this->InstanceID);
            IPS_SetName($var, "JSON Protokoll");
            IPS_SetHidden($var, true);
            SetValue($var, json_encode(array()));
        }

        // Status
        $this->RegisterProfileIntegerEx("ALARM.Status", "", "", "", Array(
                                                                            Array(0, "Aus", "", -1),
                                                                            Array(1, "Unscharf", "", 0x00FF00),
                                                                            Array(2, "Scharf", "", 0xFF6600),
                                                                            Array(3, "Alarm", "", 0xFF0000)
                                                                    ));

        $var = @IPS_GetObjectIDByIdent("STATE", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(1);
            IPS_SetIdent($var, "STATE");
            IPS_SetParent($var, $this->InstanceID);
            IPS_SetName($var, "Status");
            IPS_SetVariableCustomProfile($var, "ALARM.Status");
            $this->EnableAction("STATE");
        }

        $var = @IPS_GetObjectIDByIdent("ALARM", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(0);
            IPS_SetIdent($var, "ALARM");
            IPS_SetParent($var, $this->InstanceID);
            IPS_SetName($var, "ALARM");
            IPS_SetHidden($var, true);
        }

        $var = @IPS_GetObjectIDByIdent("TRIGGER", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetIdent($var, "TRIGGER");
            IPS_SetParent($var, $this->InstanceID);
            IPS_SetName($var, "TRIGGER");
            IPS_SetHidden($var, true);
        }

        $var = @IPS_GetObjectIDByIdent("KeepProtocolForDays", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(1);
            IPS_SetIdent($var, "KeepProtocolForDays");
            IPS_SetParent($var, $this->InstanceID);
            IPS_SetName($var, "KeepProtocolForDays");
            IPS_SetHidden($var, true);
        }
        SetValue($var, $this->ReadPropertyInteger("KeepProtocolForDays"));
        
        // check events
        $arrString = $this->ReadPropertyString("TriggerList");
        $arr = json_decode($arrString);

        if(strlen($arrString) > 0 && count($arr) > 0)
        {
            foreach($arr as $listitem)
            {
                $_EVENT = @IPS_GetObjectIDByIdent("event_".$listitem->ObjectID, $alarmControllerScriptID);
                @IPS_DeleteEvent($_EVENT);
                $_EVENT = null;
                if(!$_EVENT) {
                    switch($listitem->TriggerValue) {
                        case 0:
                            $triggerValue = 0;
                            break;
                        case 1:
                            $triggerValue = 1;
                            break;
                        case 2:
                            $triggerValue = true;
                            break;
                        case 3:
                            $triggerValue = false;
                            break;
                        default:
                            $triggerValue = null;
                            break;
                    }
                    $_EVENT = IPS_CreateEvent(0);
                    IPS_SetEventTrigger($_EVENT, 1, $listitem->ObjectID);
                    IPS_SetIdent($_EVENT, "event_".$listitem->ObjectID);
                    IPS_SetName($_EVENT, $listitem->Name);
                    IPS_SetEventTrigger($_EVENT, 4, $listitem->ObjectID);
                    IPS_SetEventTriggerValue($_EVENT, $triggerValue);
                    IPS_SetParent($_EVENT, $alarmControllerScriptID);
                    if($listitem->Active == 1)
                        IPS_SetEventActive($_EVENT, true);
                    else
                        IPS_SetEventActive($_EVENT, false);
                } 
            }
        }
    }

    public function RequestAction($Ident, $Value) { 
        switch ($Ident) 
        { 
            case "STATE":
                switch ($Value) {
                    case 0:
                        $this->Disable(true);
                        break;
                    case 1:
                        $this->Disarm(true);
                        break;
                    case 2:
                        $this->Arm(true);
                        break;
                }

                break;
            default:
                break; 
        } 
    }
    

    // PUBLIC ACCESSIBLE FUNCTIONS
    public function Disable($preExecuteScripts) {
		if(GetValue(IPS_GetObjectIDByIdent('STATE', $this->InstanceID)) == 0)
			return false;
			
        $continue = false;

        if($preExecuteScripts) {
			if($this->ReadPropertyInteger("PreDisableScript") > 0) {
				$continue = trim(IPS_RunScriptWaitEx($this->ReadPropertyInteger("PreDisableScript"), array()));
				if($continue == "1")
					$continue = true;
				else
					$continue = false;
            } else
                $continue = true;        
        }
        else
            $continue = true;
        
        if($continue) {
            $STATE = IPS_GetObjectIDByIdent('STATE', $this->InstanceID);

            $this->ResetTrigger();

            $this->LogEvent("Alarmanlage 'Aus' geschaltet.");

            SetValue($STATE, 0);
        }
    }

    public function Disarm($preExecuteScripts) {
		if(GetValue(IPS_GetObjectIDByIdent('STATE', $this->InstanceID)) == 1)
			return false;
		
        $continue = false;

        if($preExecuteScripts) {
            if($this->ReadPropertyInteger("PreDisarmScript") > 0) {
				$continue = trim(IPS_RunScriptWaitEx($this->ReadPropertyInteger("PreDisarmScript"), array()));
				if($continue == "1")
					$continue = true;
				else
					$continue = false;
            } else
                $continue = true;        
        }
        else
            $continue = true;
        
        if($continue) {
            $STATE = IPS_GetObjectIDByIdent('STATE', $this->InstanceID);
            
            $this->ResetTrigger();

            $this->LogEvent("Alarmanlage 'Unscharf' geschaltet.");

            SetValue($STATE, 1);
        }
    }

    public function Arm($preExecuteScripts) {
		if(GetValue(IPS_GetObjectIDByIdent('STATE', $this->InstanceID)) == 2)
			return false;
		
        $continue = false;

        if($preExecuteScripts) {
            if($this->ReadPropertyInteger("PreArmScript") > 0) {
				$continue = trim(IPS_RunScriptWaitEx($this->ReadPropertyInteger("PreArmScript"), array()));
				if($continue == "1")
					$continue = true;
				else
					$continue = false;
            } else
                $continue = true;
        }
        else
            $continue = true;

        if($continue) {
            $STATE = IPS_GetObjectIDByIdent('STATE', $this->InstanceID);

            $this->ResetTrigger();

            $this->LogEvent("Alarmanlage 'Scharf' geschaltet.");

            SetValue($STATE, 2);
        }
    }

    public function GetState() {
        return GetValue(IPS_GetObjectIDByIdent('STATE', $this->InstanceID));
    }
    
    public function Trigger($identifier) {
        if($this->ReadPropertyInteger("TriggerDelay") > 0) {
            sleep($this->ReadPropertyInteger("TriggerDelay"));
        }

        $STATE = IPS_GetObjectIDByIdent('STATE', $this->InstanceID);
        $ALARM = IPS_GetObjectIDByIdent('ALARM', $this->InstanceID);
        $TRIGGER = IPS_GetObjectIDByIdent('TRIGGER', $this->InstanceID);

        if(GetValue($STATE) == 2 || GetValue($STATE) == 3) {
            $this->LogEvent("Alarm ausgelöst durch: '".$identifier."'.");
            SetValue($TRIGGER, $identifier);
            SetValue($ALARM, true);
            SetValue($STATE, 3);

            IPS_SetHidden(IPS_GetObjectIDByIdent('EventList', $this->InstanceID), false);

            // Notifications
            if($this->ReadPropertyInteger("Notification_Push") == 1 && $this->ReadPropertyInteger("WFC") > 0) {
                WFC_PushNotification($this->ReadPropertyInteger("WFC"), "Ein Alarm wurde ausgelöst!", "Auslöser: '".$identifier."'", 'alarm', 0);
            }
        }
    }

    public function ResetTrigger() {
        $STATE = IPS_GetObjectIDByIdent('STATE', $this->InstanceID);
        $ALARM = IPS_GetObjectIDByIdent('ALARM', $this->InstanceID);
        $TRIGGER = IPS_GetObjectIDByIdent('TRIGGER', $this->InstanceID);

        if(GetValue($STATE) == 3) {
            $this->LogEvent("Alarmstatus wurde zurückgesetzt.");
            SetValue($TRIGGER, '');
            SetValue($ALARM, false);
            SetValue($STATE, 2);
        }
    }

    public function ShortenProtocol($days) {
        $instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}'); 
        $archive_handler = $instances[0];

        $time = time();
        $holdForSeconds = $days*24*60*60;

        SetValue(IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID), "");

        $eventList = json_decode(GetValue(IPS_GetObjectIDByIdent("EventListJSON", $this->InstanceID)), true);
        $temp = array();
        foreach ($eventList as $key => $value) {
            if($time-$key < $holdForSeconds) {
                $date = date("d. F Y H:i", $key);
                $temp[$key] = $value;
                SetValue(IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID), $date." - ".$value."<br>".GetValue(IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID)));
            }
        }

        $eventList = $temp;

        AC_DeleteVariableData($archive_handler, IPS_GetObjectIDByIdent("EventList", $this->InstanceID), 978307200, $time-$holdForSeconds);

        SetValue(IPS_GetObjectIDByIdent("EventListJSON", $this->InstanceID), json_encode($eventList));
    }

    public function ResetProtocol() {
        $instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}'); 
        $archive_handler = $instances[0];

        SetValue(IPS_GetObjectIDByIdent("EventListJSON", $this->InstanceID), json_encode(array()));

        SetValue(IPS_GetObjectIDByIdent("EventList", $this->InstanceID), "");
        AC_DeleteVariableData($archive_handler, IPS_GetObjectIDByIdent("EventList", $this->InstanceID), 0, 0);
        AC_SetLoggingStatus($archive_handler, IPS_GetObjectIDByIdent("EventList", $this->InstanceID), true);
        AC_SetAggregationType($archive_handler, IPS_GetObjectIDByIdent("EventList", $this->InstanceID), 0);
        IPS_ApplyChanges($archive_handler);

        SetValue(IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID), "");

        $this->LogEvent("Protokolle wurden manuell zurückgesetzt.");
    }
    
    // PRIVATE FUNCTIONS
    protected function LogEvent($message) {
        $time = time();
        $date = date("d. F Y H:i", $time);

        $eventListJSON = json_decode(GetValue(IPS_GetObjectIDByIdent("EventListJSON", $this->InstanceID)), true);
        $eventListJSON[$time] = $message;
        SetValue(IPS_GetObjectIDByIdent("EventListJSON", $this->InstanceID), json_encode($eventListJSON));

        SetValue(IPS_GetObjectIDByIdent("EventList", $this->InstanceID), $message);

        SetValue(IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID), $date." - ".$message."<br>".GetValue(IPS_GetObjectIDByIdent("EventListHTML", $this->InstanceID)));
    }

    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
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
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);  
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function GetParent() {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }
}

?>
