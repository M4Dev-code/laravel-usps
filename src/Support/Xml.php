<?php // src/Support/Xml.php

namespace M4dev\UspsShip\Support;

class Xml
{
    public static function buildRateV4(array $payload): string
    {
        $userId = htmlspecialchars($payload['user_id']);
        $pkg = $payload['package'];
        $id = $pkg['id'] ?? '0';
        $oz = (int) round(($pkg['weight_oz'] ?? 0));
        $zipOrig = htmlspecialchars($pkg['zip_orig']);
        $zipDest = htmlspecialchars($pkg['zip_dest']);
        $size = htmlspecialchars($pkg['size'] ?? 'REGULAR');
        $container = htmlspecialchars($pkg['container'] ?? '');
        $machinable = isset($pkg['machinable']) ? ($pkg['machinable'] ? 'true' : 'false') : 'true';

        $xml = "<RateV4Request USERID=\"{$userId}\">";
        $xml .= "<Revision>2</Revision>";
        $xml .= "<Package ID=\"{$id}\">";
        $xml .= "<Service>ALL</Service>";
        $xml .= "<ZipOrigination>{$zipOrig}</ZipOrigination>";
        $xml .= "<ZipDestination>{$zipDest}</ZipDestination>";
        $xml .= "<Pounds>0</Pounds><Ounces>{$oz}</Ounces>";
        $xml .= "<Container>{$container}</Container>";
        $xml .= "<Size>{$size}</Size>";
        $xml .= "<Machinable>{$machinable}</Machinable>";
        $xml .= "</Package>";
        $xml .= "</RateV4Request>";

        return $xml;
    }
}
