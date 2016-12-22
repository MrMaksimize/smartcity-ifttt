<?php
// Routes

$app->post('/twilio/request/log', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Twilio traffic '/twilio/request/log' route - success");
    var_dump($request->getBody());
});

$app->get('/', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Logging traffic '/' route - ");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/ifttt/v1/status', function ($request, $response, $args) {
    return $response->withStatus(200);
});

$app->get('/deploy/go', function ($request, $response, $args) {
    $output = shell_exec('ls -lart');
    echo "<pre>$output</pre>";
});

$app->post('/ifttt/v1/test/setup', function ($request, $response, $args) {

    $data = [
        'data' => [
            'samples' => [
                'triggers' => [
                    'junk_pickup' => [
                        'what_is_your_address' => '121 Freeman AVE'
                    ],
                    'trash_recycling_pickup' => [
                        'what_is_your_address' => '121 Freeman AVE'
                    ]
                ]
            ]
        ]
    ];

    return $response->withStatus(200)
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->write(json_encode($data));
});

$app->post('/ifttt/v1/triggers/air_quality', function ($request, $response, $args) {
    $error_msgs = array();

    $request_data = json_decode($request->getBody()->getContents(), true);

    if( ! isset( $request_data['triggerFields'] ) ) { $error_msgs[] = array('message' => 'TriggerFields is not set'); }

    $limit = isset( $request_data['limit'] ) && ! empty($request_data['limit']) ? $request_data['limit'] : ( isset( $request_data['limit'] ) && $request_data['limit'] === 0 ? 0 : null );

    if( empty($error_msgs) )
    {
        $stream_data = stream_context_create([
            'ssl' => [
                'ciphers' => 'ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:SRP-DSS-AES-256-CBC-SHA:SRP-RSA-AES-256-CBC-SHA:SRP-AES-256-CBC-SHA:DHE-RSA-AES256-SHA:DHE-DSS-AES256-SHA:DH-RSA-AES256-SHA:DH-DSS-AES256-SHA:DHE-RSA-CAMELLIA256-SHA:DHE-DSS-CAMELLIA256-SHA:DH-RSA-CAMELLIA256-SHA:DH-DSS-CAMELLIA256-SHA:ECDH-RSA-AES256-SHA:ECDH-ECDSA-AES256-SHA:AES256-SHA:CAMELLIA256-SHA:PSK-AES256-CBC-SHA:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:SRP-DSS-AES-128-CBC-SHA:SRP-RSA-AES-128-CBC-SHA:SRP-AES-128-CBC-SHA:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA:DH-RSA-AES128-SHA:DH-DSS-AES128-SHA:DHE-RSA-SEED-SHA:DHE-DSS-SEED-SHA:DH-RSA-SEED-SHA:DH-DSS-SEED-SHA:DHE-RSA-CAMELLIA128-SHA:DHE-DSS-CAMELLIA128-SHA:DH-RSA-CAMELLIA128-SHA:DH-DSS-CAMELLIA128-SHA:ECDH-RSA-AES128-SHA:ECDH-ECDSA-AES128-SHA:AES128-SHA:SEED-SHA:CAMELLIA128-SHA:PSK-AES128-CBC-SHA:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:ECDH-RSA-RC4-SHA:ECDH-ECDSA-RC4-SHA:RC4-SHA:RC4-MD5:PSK-RC4-SHA:ECDHE-RSA-DES-CBC3-SHA:ECDHE-ECDSA-DES-CBC3-SHA:SRP-DSS-3DES-EDE-CBC-SHA:SRP-RSA-3DES-EDE-CBC-SHA:SRP-3DES-EDE-CBC-SHA:EDH-RSA-DES-CBC3-SHA:EDH-DSS-DES-CBC3-SHA:DH-RSA-DES-CBC3-SHA:DH-DSS-DES-CBC3-SHA:ECDH-RSA-DES-CBC3-SHA:ECDH-ECDSA-DES-CBC3-SHA:DES-CBC3-SHA:PSK-3DES-EDE-CBC-SHA:',
                'protocol_version' => 'tls1'
            ]
        ]);

        $res = file_get_contents('https://aaws.louisvilleky.gov/api/v1/Monitor/CityAQI', false, $stream_data);

        if( ! empty( $res ) )
        {
            $reqdata = json_decode($res, true);

            if( ! empty( $reqdata ) ) {

                //$air_quality = $reqdata['AirQuality']['Index'];
                $air_quality = rand(1, 100);
                $aql = 'N/A';

                switch($air_quality)
                {
                    case ( $air_quality <= 50 ):
                        $aql = 'Good';
                        break;
                    case ( $air_quality <= 100 ):
                        $aql = 'Moderate';
                        break;
                    case ( $air_quality <= 150 ):
                        $aql = 'Unhealthy for Sensitive Groups';
                        break;
                    case ( $air_quality <= 200 ):
                        $aql = 'Unhealthy';
                        break;
                    case ( $air_quality <= 300 ):
                        $aql = 'Very Unhealthy';
                        break;
                    case ( $air_quality >= 300 ):
                        $aql = 'Hazardous';
                        break;
                }

                //insert NEW RECORD!
                $this->db->table('air_quality_record')->insertGetId(array(
                    'index_value' => $air_quality,
                    'label' => $aql,
                    'date_created' => date('Y-m-d H:i:s')
                ));


                //get air qulity's
                $records = $this->db->table('air_quality_record')
                    ->orderBy('date_created', 'desc')
                    ->limit($limit)
                    ->get();

                $newarr['data'] = array();

                foreach( $records as $record )
                {
                    $time = new DateTime($record->date_created);
                    $time_set = $time->format(DateTime::ATOM);

                    switch(rand(1, 100))
                    {
                        case ( $air_quality <= 50 ):
                            $color = 'Green';
                            break;
                        case ( $air_quality <= 100 ):
                            $color = 'Yellow';
                            break;
                        case ( $air_quality <= 150 ):
                            $color = 'Orange';
                            break;
                        case ( $air_quality <= 200 ):
                            $color = 'Red';
                            break;
                        case ( $air_quality <= 300 ):
                            $color = 'Purple';
                            break;
                        case ( $air_quality >= 300 ):
                            $color = 'Maroon';
                            break;
                    }

                    $newarr['data'][] = array(
                        'id' => $record->id,
                        'air_quality_level' => $record->index_value,
                        'air_quality_label' => $record->label,
                        'air_quality_color' => $color,
                        'created_at' => $time_set,
                        'meta' => array(
                            'id' => $record->id,
                            'timestamp' => strtotime($record->date_created)
                        )
                    );
                }

                return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->write(json_encode($newarr));
            } else {
                $error_msgs[] = array('status'=> 'SKIP', 'message' => 'Properties need to be set');
            }
        } else {
            $error_msgs[] = array('status'=> 'SKIP', 'message' => 'Response is empty');
        }
    }
    $error = array('errors' => $error_msgs);
    return $response->withStatus(400)
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->write(json_encode($error));


});


$app->get('/deploy', function ($request, $response, $args) {

});
