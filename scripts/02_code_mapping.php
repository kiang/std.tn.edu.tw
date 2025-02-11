<?php

$zonePath = dirname(__DIR__) . '/docs/zones';
$zoneFiles = glob($zonePath . '/*.json');

$cunliMap = [];

foreach ($zoneFiles as $zoneFile) {
    $p = pathinfo($zoneFile);
    $stage = $p['filename'];
    $zones = json_decode(file_get_contents($zoneFile), true);
    foreach ($zones as $code => $school) {
        foreach ($school['zones'] as $zone) {
            $key = $zone['area'] . $zone['cunli'];
            switch ($key) {
                case '仁德區仁德區':
                    $key = '仁德區仁德里';
                    break;
                case '西港區檨林里':
                    $key = '西港區[檨]林里';
                    break;
                case '麻豆區晉江里':
                    $key = '麻豆區晋江里';
                    break;
                case '新化區那拔里':
                    $key = '新化區[那]拔里';
                    break;
                case '龍崎區石𥕢里':
                    $key = '龍崎區石[曹]里';
                    break;
                case '安南區公塭里':
                    $key = '安南區公[塭]里';
                    break;
                case '安南區塭南里':
                    $key = '安南區[塭]南里';
                    break;
            }

            if (!isset($cunliMap[$key])) {
                $cunliMap[$key] = [
                    '國中' => [],
                    '國小' => []
                ];
            }
            $cunliMap[$key][$stage][] = [
                'scope' => $zone['scope'],
                'code' => $code,
                'name' => $school['name']
            ];
        }
    }
}

$newFc = [
    'type' => 'FeatureCollection',
    'features' => []
];

$cunlis = json_decode(file_get_contents('/home/kiang/public_html/taiwan_basecode/cunli/geo/20240807.json'), true);

$cunliToSchool = $schoolToCunli = [];
foreach ($cunlis['features'] as $cunli) {
    if (!isset($cunli['properties']['COUNTYNAME']) || $cunli['properties']['COUNTYNAME'] != '臺南市') {
        continue;
    }
    $key = $cunli['properties']['TOWNNAME'] . $cunli['properties']['VILLNAME'];
    if (isset($cunliMap[$key])) {
        $cunliToSchool[$cunli['properties']['VILLCODE']] = $cunliMap[$key];
        foreach($cunliMap[$key] as $stage => $schools) {
            foreach($schools as $school) {
                if(!isset($schoolToCunli[$school['code']])) {
                    $schoolToCunli[$school['code']] = [];
                }
                $schoolToCunli[$school['code']][] = $cunli['properties']['VILLCODE'];
            }
        }
    }
    $newFc['features'][] = $cunli;
}

file_put_contents($zonePath . '/school_to_cunli.json', json_encode($schoolToCunli, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($zonePath . '/cunli_to_school.json', json_encode($cunliToSchool, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
