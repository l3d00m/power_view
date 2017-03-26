<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="refresh" content="600">

    <title>Strom</title>
    <script src="js/jquery.js"></script>
    <style>
        html{
            font-family: Verdana;   
            font-size: 20px;            
        }
        .hidden {
            visibility: hidden;
        }
        td, th {
            text-align: center;
            padding: 4px;
            border: 1px solid black;
        }
    </style>
</head>

<body>
<?php 
    $ini_array = parse_ini_file("config.ini");
    $solar_urls = $ini_array['solar_url'];
    $solar_phasen = $ini_array['solar_phase'];
    $verbrauch = $ini_array['verbrauch'];
    $powermeter_url = $ini_array['powermeter_url'];
    $refresh_rate = $ini_array['refresh_rate'];
    $max_power = $ini_array['max_power'];
?>
<div class='container hidden'>
    <table>
     <tr><th></th><th>Phase 1</th><th>Phase 2</th><th>Phase 3</th></tr>
     <tr>
        <td>Produktion: </td>
        <td>
            <span class='production_current_value1'>--</span>W
        </td>
        <td>
            <span class='production_current_value2'>--</span>W
        </td>
        <td>
            <span class='production_current_value3'>--</span>W
        </td>
     </tr>
     <tr>
        <td>Verbrauch: </td>
        <td>
            <span class='consumption_value1'></span>W
        </td>
        <td>
            <span class='consumption_value2'></span>W
        </td>
        <td>
            <span class='consumption_value3'></span>W
        </td>
     </tr>
     <tr>
        <td>Verfügbar: </td>
        <td>
            <span class='result_value1'>--W</span>
        </td>
        <td>
            <span class='result_value2'>--W</span>
        </td>
        <td>
            <span class='result_value3'>--W</span>
        </td>
     </tr>
    </table>
</div> 
<script>
    power();
    function power() {
        var solar_urls = <?php echo json_encode($solar_urls); ?>;
        var solar_phasen = <?php echo json_encode($solar_phasen); ?>;
        var promises = new Array();
        for(key in solar_urls){
            var call = solarRequest(solar_urls[key], solar_phasen[key]);
            promises.push(call);
        }
        
        $.when.apply($, promises).done(fillSolarPower);
       
        
        $.getJSON( "/power/requests.php", 
                {"url" : "<?php echo $powermeter_url.'/query_live.php'; ?>"}, 
                function(data) {
            var value0 = parseInt(data['w0']);
            $('.consumption_value1').text(value0);
            var value1 = parseInt(data['w1']);
            $('.consumption_value2').text(value1);
            var value2 = parseInt(data['w2']);
            $('.consumption_value3').text(value2);
            $('.container').removeClass('hidden');
            
            calculate_result();
        });
        //repeat over and over
        setTimeout(power, "<?php echo $refresh_rate; ?>");
    }
    
    function calculate_result(){
        for (var column = 1; column <= 3; column++) { 
            var prod = parseInt($('.production_current_value' + column).text());
            var con = parseInt($('.consumption_value' + column).text());
            var diff = prod - con;
            if (diff > 0){
                $('.result_value' + column).text(diff + "W");
            } else {
                $('.result_value' + column).text("--W");
            }
            $('.result_value' + column).css('color', colorInt(diff));
            $('.container').removeClass('hidden');    
        }  
    }
    // Return RGB color based on int (0= dark red; 3000 = bright green)
    // Source: http://jsfiddle.net/1tyfd2ru/
    function colorInt(v){
        return "rgb(" + (255-conv(v)) + "," + conv(v) + ",0)";
    }

    function conv(x){
        return Math.floor((x - 100) / <?php echo $refresh_rate; ?> * 255);
    }
    
    function solarRequest(url, phase){
        return $.ajax({
            url: '/power/requests.php',
            data: {"url" : url + "/index.xml", "modify_phase": phase},
            success: function(response) {},
            error: function (response) {
                $('.production_current_value' + phase).text('--');
                console.log("Error while getting production");
            }
        });
    }
    
    function fillSolarPower(){
        var responses = arguments;
        var sum_phase1 = 0;
        var sum_phase2 = 0;
        var sum_phase3 = 0;
        for(i in responses){
            if ($.inArray('success', responses) != -1){
                 //Array only contains a single result in the first "depth"
                xml = $.parseXML(responses[0]);
            } else {
                //Array contains multiple results in it's own arrays
                xml = $.parseXML(responses[i][0]);    
            }
            var current_val = parseInt($(xml).find("SolarPanel").text());
            var phase = parseInt($(xml).find("phase").text());
            switch (phase){
                case 1:
                    sum_phase1 += current_val;
                    break;
                case 2:
                    sum_phase2 += current_val;
                    break;
                case 3:
                    sum_phase3 += current_val;
                    break;  
            }
            if ($.inArray('success', responses) != -1){
                // Don't iterate through a single result 
                break;
            }
        }  
        $('.production_current_value1').text(sum_phase1);
        $('.production_current_value2').text(sum_phase2);
        $('.production_current_value3').text(sum_phase3);
        calculate_result();
    }
</script>
</body>
</html>