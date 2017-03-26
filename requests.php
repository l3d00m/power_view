<?php 
    if (isset($_REQUEST['url'])){
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $_REQUEST['url']); 

        //return the result in exec, don't just print
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //Execute
        $result = curl_exec($ch);
        
        if (isset($_REQUEST['modify_phase'])){
            $xml = simplexml_load_string($result);
            $xml->phase = $_REQUEST['modify_phase'] ;
            $result = $xml->asXML();
        }
        echo $result;

        // close curl resource to free up system resources
        curl_close($ch);  
    }
?>