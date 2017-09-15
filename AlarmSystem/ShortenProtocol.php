<?
    if($_IPS['SENDER'] == "TimerEvent") {
        $moduleID = IPS_GetParent($_IPS['SELF']);
        $days = GetValue(IPS_GetObjectIDByIdent("KeepProtocolForDays", $moduleID));

        if($days > 0) {
            ALRM_ShortenProtocol($moduleID, $days);
        }
    }
?>