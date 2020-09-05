  <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $url = "https://techno:4EPf3nme@172.16.66.20:8443/sdc/services/PerformanceManagementRetrievalExtns";
    $input_xml = '<soapenv:Envelope xmlns:sdc="sdcNbi" xmlns:tmf="tmf854.v1" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header>
         <tmf:header tmf854Version="?" extAuthor="?" extVersion="?">
            <tmf:msgName>GetPerformanceMonitoringDataForObjectsRequest</tmf:msgName>
            <tmf:msgType>REQUEST</tmf:msgType>
            <tmf:communicationPattern>SimpleResponse</tmf:communicationPattern>
            <tmf:communicationStyle>RCP</tmf:communicationStyle>
         </tmf:header>
      </soapenv:Header>
      <soapenv:Body>
         <sdc:GetPerformanceMonitoringDataForObjectsRequest>
            <sdc:pmInputList>
               <sdc:pmObjectSelect>
                  <tmf:mdNm>AMS</tmf:mdNm>
                  <tmf:meNm>olt0.test02</tmf:meNm>
                  <tmf:propNm>/Type=Optical Measurements/R1.S1.LT3.PON16.ONT2</tmf:propNm>
               </sdc:pmObjectSelect>
               <sdc:pmParameterList>
                  <sdc:pmParameter>
                     <sdc:pmParameterName>gponOntAniOpInfoTxOpticalSignalLevel</sdc:pmParameterName>
                     <sdc:pmParemeterMaxIntervals>1</sdc:pmParemeterMaxIntervals>
                  </sdc:pmParameter>
               </sdc:pmParameterList>
            </sdc:pmInputList>
         </sdc:GetPerformanceMonitoringDataForObjectsRequest>
      </soapenv:Body>
    </soapenv:Envelope>';
    try {

        //setting the curl parameters

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input_xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept-Encoding: gzip,deflate',
            'Content-Type: text/xml;charset=UTF-8',
            'Host: 172.16.66.20:8443'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);

        $response = curl_exec($ch);
        if ($response === false)
            $response = curl_error($ch);
        print_r(htmlspecialchars($response));

        curl_close($ch);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    ?>