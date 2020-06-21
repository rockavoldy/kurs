<?php

error_reporting(0);

function getCurl()
{
    $url = "https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak";
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
    return $dom;
}

function getDataKurs()
{
    if (!$dom = getDom()) {
        return false;
    }

    $rows = array();

    $table = $dom->getElementsByTagName('table')[0];

    foreach ($table->getElementsByTagName('tr') as $tr) {
        $cells = array();
        $i = 0;

        foreach ($tr->getElementsByTagName('td') as $r) {
            if ($i == 1) {
                $cells['mata_uang'] = trim($r->nodeValue);
                $cells['simbol'] = trim(substr($r->nodeValue, -4, 3));
            } else if ($i == 2) {
                $cells['nilai'] = trim($r->nodeValue);
            } else if ($i == 3) {
                $cells['perubahan'] = trim($r->nodeValue);
            }
            $i++;
        }

        if ($cells['simbol'] != null) {
            $rows[] = $cells;
        }
    }

    return $rows;
}

function getTanggalBerlaku()
{
    if (!$dom = getDom()) {
        return false;
    }

    return explode(" - ", substr(trim($dom->getElementsByTagName('p')[1]->nodeValue), 17));
}

function generateJson()
{
    $berlaku_mulai = date_create(getTanggalBerlaku()[0]);
    $berlaku_sampai = date_create(getTanggalBerlaku()[1]);
    $nama = "kurs-" . date_format($berlaku_mulai, "dmy") . ".json";
    $fp = fopen($nama, 'w');
    $data = array(
        'tanggal_generate' => date('Y-m-d H:i:s'),
        'berlaku_mulai' => date_format($berlaku_mulai, "Y-m-d"),
        'berlaku_sampai' => date_format($berlaku_sampai, "Y-m-d"),
        'data' => getDataKurs()
    );

    fwrite($fp, json_encode($data));
    fclose($fp);

    return $nama;
}


function main()
{
    echo generateJson();
}

main();
