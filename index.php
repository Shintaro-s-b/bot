<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

$app->post('/callback', function (Request $request) use ($app) {
/*
    $body = json_decode($request->getContent(), true);
    foreach ($body['result'] as $msg) {

        $word = $msg['content']['text'];
        $api_res_json = callTranslateAPI( $word );
        $res_msg = "$word の意味は...\n";
        foreach( $api_res_json['tuc'] as $tuc ) {
            if( !empty( $tuc['phrase']['text'] ) ) {
                $res_msg .= $tuc['phrase']['text'] . "\n";
            }
        }
    }
    $res_msg .= "だよっ！<(＞ ∇ ＜ )";
*/
    $res_msg = createMessage( $body );

    $resContent = $msg['content'];
    $resContent['text'] = $res_msg;

    $options = createOptions( $msg, $resContent );

    sendMessage( $options );

    return 'OKOK';
});

$app->run();

function callTranslateAPI( $word ) {
    $api_res = file_get_contents("https://glosbe.com/gapi/translate?from=en&dest=ja&format=json&phrase=$word&pretty=true");
    return json_decode( $api_res, true );
}

function createMessage( $body ) {
    foreach ( $body[ 'result' ] as $msg ) {
        $word = $msg[ 'content' ][ 'text' ];
        $api_res_json = callTranslateAPI( $word );
        $res_msg = "$word の意味は...\n";
        foreach( $api_res_json[ 'tuc' ] as $tuc ) {
            if( !empty( $tuc[ 'phrase' ][ 'text' ] ) ) {
                $res_msg .= $tuc[ 'phrase' ][ 'text' ] . "\n";
            }
        }
    }
    $res_msg .= "だよっ！<(＞ ∇ ＜ )";
    return $res_msg;
}

function createOptions($msg, $resContent) {
    return $options = [
        'body' => json_encode([
            'to' => [ $msg[ 'content' ][ 'from' ] ],
            'toChannel' => 1383378250, # Fixed value
            'eventType' => '138311608800106203', # Fixed value
            'content' => $resContent,
        ]),
        'headers' => [
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Line-ChannelID' => getenv( 'LINE_CHANNEL_ID' ),
            'X-Line-ChannelSecret' => getenv( 'LINE_CHANNEL_SECRET' ),
            'X-Line-Trusted-User-With-ACL' => getenv( 'LINE_CHANNEL_MID' ),
        ],
        'proxy' => [
            'https' => getenv( 'FIXIE_URL' ),
        ],
    ];
}

function sendMessage( $options ) {
    try {
        $client = new GuzzleHttp\Client();
        $client->request( 'post', 'https://trialbot-api.line.me/v1/events', $options );
    } catch ( Exception $e ) {
        error_log( $e->getMessage() );
    }
}
