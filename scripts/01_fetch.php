<?php

require 'vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

$browser = new HttpBrowser(HttpClient::create([
    'verify_peer' => false
]));

$headers = [
    'Accept' => '*/*',
    'Accept-Language' => 'zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control' => 'no-cache',
    'Connection' => 'keep-alive',
    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
    'Origin' => 'https://std.tn.edu.tw',
    'Pragma' => 'no-cache',
    'Referer' => 'https://std.tn.edu.tw/sis/anonyquery/SchoolDistrict.aspx',
    'Sec-Fetch-Dest' => 'empty',
    'Sec-Fetch-Mode' => 'cors',
    'Sec-Fetch-Site' => 'same-origin',
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
    'X-MicrosoftAjax' => 'Delta=true',
    'X-Requested-With' => 'XMLHttpRequest',
    'sec-ch-ua' => '"Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
    'sec-ch-ua-mobile' => '?0',
    'sec-ch-ua-platform' => '"Linux"',
    'Cookie' => ''
];


// First get the initial page to get the form tokens
$crawler = $browser->request('GET', 'https://std.tn.edu.tw/sis/anonyquery/SchoolDistrict.aspx');
$schoolNode = $crawler->filter('#ContentPlaceHolder1_list_sch')->html();
$lines = explode("\n", $schoolNode);
$schools = [];
foreach ($lines as $line) {
    $parts = preg_match_all('/<option value="(\d+)">(.*?)<\/option>/', $line, $matches);
    if (empty($parts)) continue;
    $schools[] = [
        'id' => $matches[1][0],
        'name' => $matches[2][0]
    ];
}

$zonePath = dirname(__DIR__) . '/docs/zones';
if (!is_dir($zonePath)) {
    mkdir($zonePath, 0777, true);
}
$zoneFile = $zonePath . '/國小.json';
$zones = [];
if (file_exists($zoneFile)) {
    $zones = json_decode(file_get_contents($zoneFile), true);
}

foreach ($schools as $school) {
    if (isset($zones[$school['id']])) {
        continue;
    }
    $zones[$school['id']] = [
        'name' => $school['name'],
        'zones' => []
    ];
    $form = $crawler->filter('form')->form();
    $form->setValues([
        'ctl00$ContentPlaceHolder1$ddl_stage' => '國小',
        'ctl00$ContentPlaceHolder1$list_sch' => $school['id'],
    ]);

    $crawler = $browser->submit($form, [], $headers);
    try {
        $zoneNode = $crawler->filter('#ContentPlaceHolder1_gv')->html();
        $lines = explode('</tr>', $zoneNode);
        foreach ($lines as $line) {
            $parts = explode('</td>', $line);
            if (count($parts) === 6) {
                foreach ($parts as $k => $part) {
                    $parts[$k] = trim(strip_tags($part));
                }
                $zones[$school['id']]['zones'][] = [
                    'area' => $parts[2],
                    'cunli' => $parts[3],
                    'scope' => $parts[4],
                ];
            }
        }
    } catch (\Throwable $th) {
        echo $th->getMessage();
    }

    file_put_contents($zoneFile, json_encode($zones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// get form from crawler
$form = $crawler->filter('form')->form();
$form->remove('ctl00$ContentPlaceHolder1$list_sch');


$form->setValues([
    'ctl00$ContentPlaceHolder1$ddl_stage' => '國中',
]);

// submit form
$crawler = $browser->submit($form, [], $headers);

$schoolNode = $crawler->filter('#ContentPlaceHolder1_list_sch')->html();
$lines = explode("\n", $schoolNode);
$schools = [];
foreach ($lines as $line) {
    $parts = preg_match_all('/<option value="(\d+)">(.*?)<\/option>/', $line, $matches);
    if (empty($parts)) continue;
    $schools[] = [
        'id' => $matches[1][0],
        'name' => $matches[2][0]
    ];
}

$zoneFile = $zonePath . '/國中.json';
$zones = [];
if (file_exists($zoneFile)) {
    $zones = json_decode(file_get_contents($zoneFile), true);
}

foreach ($schools as $school) {
    if (isset($zones[$school['id']])) {
        continue;
    }
    $zones[$school['id']] = [
        'name' => $school['name'],
        'zones' => []
    ];
    $form = $crawler->filter('form')->form();
    $form->setValues([
        'ctl00$ContentPlaceHolder1$ddl_stage' => '國中',
        'ctl00$ContentPlaceHolder1$list_sch' => $school['id'],
    ]);

    $crawler = $browser->submit($form, [], $headers);
    try {
        $zoneNode = $crawler->filter('#ContentPlaceHolder1_gv')->html();
        $lines = explode('</tr>', $zoneNode);
        foreach ($lines as $line) {
            $parts = explode('</td>', $line);
            if (count($parts) === 6) {
                foreach ($parts as $k => $part) {
                    $parts[$k] = trim(strip_tags($part));
                }
                $zones[$school['id']]['zones'][] = [
                    'area' => $parts[2],
                    'cunli' => $parts[3],
                    'scope' => $parts[4],
                ];
            }
        }
    } catch (\Throwable $th) {
        echo $th->getMessage();
    }

    file_put_contents($zoneFile, json_encode($zones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}