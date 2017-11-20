<?php
require 'vendor/autoload.php';
require 'config.php';

$client = new Maknz\Slack\Client($settings["slack"]["webhook_url"], $settings["slack"]);
$guzzle = new GuzzleHttp\Client();

function getTravelToWorkDistances(&$fields, $lat, $lon) {
    global $settings;

    if(empty($settings["google"]["key"]) || empty($settings["google"]["addresses"])) return;

    $addresses = "";
    $return = [];

    foreach($settings["google"]["addresses"] as $place => $address) {
        $addresses .= rawurlencode($address)."|";

        $return[] = [
            "title" => "Distance to ".$place,
            "value" => "Unknown",
            "short" => true
        ];
    }

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&mode=".$settings["google"]["transport"]."&origins=".$lat.",".$lon."&destinations=".rtrim($addresses, "|")."&key=".$settings["google"]["key"];

    $guzzle = new GuzzleHttp\Client();
    $res = $guzzle->request('GET', $url);

    $json = json_decode($res->getBody());

    if(isset($json->rows[0]->elements)) {
        $numElements = count($json->rows[0]->elements);
        for($i = 0; $i < $numElements; $i++) {
            $place = $json->rows[0]->elements[$i];
            if($place->status == "OK") {
                $return[$i]["value"] = $place->distance->text." - ".$place->duration->text." (".$settings["google"]["transport"].")";
            } else {
                unset($return[$i]);
            }
        }

        $fields = array_merge($fields, $return);
    }
}

function getBroadbandSpeeds(&$fields, $lat, $lon) {
    // From what I can tell, only JSONP is supported. Highly recommend checking this endpoint out, has some interesting
    // stuff in it. And removing some of the query params breaks the page, so I'm gonna leave them in
    $url = "https://chorus-viewer.wivolo.com/viewer-chorus/jsonp/location-details?lat=".$lat."&lng=".$lon."&debug=0&zoom=1&maplayers=3&search_type=X&callback=A";

    $guzzle = new GuzzleHttp\Client();
    $res = $guzzle->request('GET', $url);

    $res = (string)$res->getBody();
    // Strip the JSONP callback
    $res = substr($res, strpos($res, '('));
    $json = json_decode(trim($res, '();'));

    if(isset($json->services) && $json->success) {
        $services = $json->services;
        if(!$services->fibre->available) {
            $f = "No";
            if(!empty($services->fibre->timing)) $f .= " - ".$services->fibre->timing;
        } else {
            $f = "Yes";
            if(!empty($services->fibre->speed)) $f .= " - ".$services->fibre->speed;
        }

        if(!$services->vdsl->available) {
            $v = "No";
            if(!empty($services->vdsl->timing)) $v .= " - ".$services->vdsl->timing;
        } else {
            $v = "Yes";
            if(!empty($services->vdsl->speed)) $v .= " - ".$services->vdsl->speed;
        }

        $fields = array_merge($fields, [
            [
                "title" => "Has Fibre?",
                "value" => $f,
                "short" => true
            ],
            [
                "title" => "Has VDSL?",
                "value" => $v,
                "short" => true
            ]
        ]);
    }
}

function getElevation(&$fields, $lat, $lon) {
    global $settings;

    if(empty($settings["google"]["key"]) || !$settings["google"]["elevation"]["enabled"]) return false;

    $url = "https://maps.googleapis.com/maps/api/elevation/json?locations=".$lat.",".$lon."&key=".$settings["google"]["key"];

    $guzzle = new GuzzleHttp\Client();
    $res = $guzzle->request('GET', $url);

    $json = json_decode($res->getBody());

    $fieldReturn = [
        "title" => "Elevation",
        "value" => "Unknown",
        "short" => true
    ];

    $returnVal = true;

    if(isset($json->results[0])) {
        $place = $json->results[0];
        $val = number_format($place->elevation, 0);

        $fieldReturn["value"] = $val." meters";

        if($val <= $settings["google"]["elevation"]["threshold"]) {
            $diff = number_format(abs($val - $settings["google"]["elevation"]["threshold"]), 0);
            
            $returnVal = [
                'text'     => '*:rotating_light: This property is below your elevation threshold by '.$diff.' meters. Check your local tsunami risk zone map :rotating_light:*',
                'fallback' => 'Elevation is below specified threshold',
                'color'    => 'danger',
                'mrkdwn_in' => ['text']
            ];
        }

        $fields = array_merge($fields, [$fieldReturn]);
    }

    return $returnVal;
}

$fromDate = rawurlencode(date("Y-m-d\TH:i:s", strtotime($settings["new_properties_since"])));

// Get all recent properties from the Trade Me API
$url = 'https://api.trademe.co.nz/v1/Search/Property/Rental.json?date_from='.$fromDate.'&'.http_build_query($settings["trademe"]["search"]);

$res = $guzzle->request('GET', $url, [
    'headers' => [
        'Authorization' => 'OAuth oauth_consumer_key="'.$settings["trademe"]["consumer_key"].'", oauth_signature_method="PLAINTEXT", oauth_signature="'.$settings["trademe"]["consumer_secret"].'&"'
    ]
]);

$json = json_decode($res->getBody());

if(property_exists($json, "List") && !empty($json->List)) {
    foreach($json->List as $house) {
        $lat = $house->GeographicLocation->Latitude ?? 0;
        $lon = $house->GeographicLocation->Longitude ?? 0;
        
        $fields = [
            [
                "title" => "Location",
                "value" => $house->Address ?? "n/a"." <https://maps.google.com/maps?z=12&t=m&q=loc:".$lat."+".$lon."|(open in Google Maps)>",
                "short" => true
            ],
            [
                "title" => "Rent",
                "value" => $house->PriceDisplay ?? "n/a",
                "short" => true
            ],
            [
                "title" => "Available From",
                "value" => $house->AvailableFrom ?? "n/a",
                "short" => true
            ],
            [
                "title" => "Furnishings",
                "value" => $house->Whiteware ?? "n/a",
                "short" => true
            ],
            [
                "title" => "Bedrooms",
                "value" => $house->Bedrooms ?? "n/a",
                "short" => true
            ],
            [
                "title" => "Bathrooms",
                "value" => $house->Bathrooms ?? "n/a",
                "short" => true
            ]
        ];

        getBroadbandSpeeds($fields, $lat, $lon);
        getTravelToWorkDistances($fields, $lat, $lon);
        
        $message = $client->createMessage();

        $elevation = getElevation($fields, $lat, $lon);

        $message->to($settings["slack"]["channel"])->attach(new Maknz\Slack\Attachment([
            "fallback"   => $house->Title,
            "title"      => $house->Title,
            "title_link" => "https://trademe.co.nz/".$house->ListingId,
            "image_url"  => $house->PictureHref,
            "fields"     => $fields
        ]));

        if(is_array($elevation)) {
            $message->attach(new Maknz\Slack\Attachment($elevation));
        }

        $message->setText($house->Title.", Available ".$house->AvailableFrom ?? "unknown")->send();
    }
}
