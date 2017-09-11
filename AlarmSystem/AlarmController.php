<?  
    if(isset($_IPS['SENDER']) && isset($_IPS['EVENT']) && isset($_IPS['TRIGGER']) && $_IPS['SENDER'] == "Variable" && $_IPS['TRIGGER'] == "OnValue") {
        $trigger = IPS_GetObjecT($_IPS['EVENT']);
        
        ALRM_Trigger(IPS_GetParent($_IPS['SELF']), $trigger['ObjectName']);
    }
        
?>