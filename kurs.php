<?php

error_reporting(0);

function getCurl()
{
    $url = "https://fiskal.kemenkeu.go.id/dw-kurs-db.asp";
    $ua = "Googlebot/2.1 (http://www.googlebot.com/bot.html)";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    if (!$content = curl_exec($ch)) {
        return false;
    }

    curl_close($ch);

    return $content;
}

function getDom()
{
    if (!$content = getCurl()) {
        return false;
    }

    $dom = new DOMDocument;
    $dom->loadHTML($content);

    $rows = array();

    $table = $dom->getElementsByTagName('table')[0];
    // print_r($tbody);

    foreach ($table->getElementsByTagName('tr') as $tr) {
        $cells = array();
        $i = 0;
        foreach ($tr->getElementsByTagName('td') as $r) {
            if ($i == 1) {
                $cells['mata_uang'] = $r->nodeValue;
                $cells['simbol'] = substr($r->nodeValue, -4, 3);
            } else if ($i == 2) {
                $cells['nilai'] = $r->nodeValue;
            } else if ($i == 3) {
                $cells['perubahan'] = $r->nodeValue;
            }
            $i++;
        }
        $rows[] = $cells;
    }		
        
    return $rows;
}

function generateJson()
{
    $fp = fopen('kurs.json', 'w');
    fwrite($fp, json_encode(getDom()));
    fclose($fp);
}

generateJson();
?>