<?php

$ignore = [
    "SymconBC",
    "SymconBroken",
    "SymconHelper",
    "SymconIncompatible",
    "SymconMisc",
    "SymconTest",
    "SymconWebinar",
    "WeidmannEmlog"
];

$store = [
    "Energiezaehler",
    "Gardena",
    "HomeConnect",
    "Rechenmodule",
    "SymconConfiguration",
    "SymconGraph",
    "SymconLJ",
    "SymconMH",
    "SymconREHAU",
    "SymconReport",
    "SymconSpotify"
];

$extern = [
    "SyncMySQL",
    "SymconREHAU",
    "SymconMH",
    "SymconLJ",
    "FIWARE"
];

$unfinished = [
    "Sonos",
    "WaermemengenZaehler",
];

$url = [
    "Alexa",
    "Assistant",
    "Gardena",
    "HomeConnect",
    "SymconLJ"
];

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Awesome-PHP\r\n"
    ]
];

$context = stream_context_create($opts);

$repos = json_decode(file_get_contents("https://api.github.com/user/14051084/repos?per_page=100", false, $context), true);

if(sizeof($repos) == 100) {
    die("We need to implement pagination");
}

$modules = json_decode(file_get_contents("https://symcon-store.s3.eu-west-1.amazonaws.com/modules.json"));

function hasURL($name)
{
    global $modules, $url;
    foreach ($modules as $module) {
        if (!isset($module->url)) {
            continue;
        }
        if (strpos($module->url, "/symcon/" . $name) !== false) {
            return true;
        }
    }
    // Fallback to manually checked modules
    return in_array($name, $url);
}

function isInStore($name)
{
    global $modules, $store;
    foreach ($modules as $module) {
        if ($module->name == $name) {
            return true;
        }
    }
    // Fallback to manually checked modules
    return in_array($name, $store);
}

function filterRepo($repo)
{
    if (substr($repo, 0, 4) == "Skin") {
        return true;
    }
    if (substr($repo, 0, 5) == "Style") {
        return true;
    }
    if (substr($repo, 0, 7) == "action-") {
        return true;
    }
    if (in_array($repo, ["AndroidSDK", "Status"])) {
        return true;
    }
    return false;
}

$content = "### Übersicht aller PHP-Module der Symcon GmbH und deren Check Status" . PHP_EOL;
$content .= PHP_EOL;
$content .= "Name | Style | Tests | Store | URL" . PHP_EOL;
$content .= "---- | ----- | ----- | ----- | ---" . PHP_EOL;

$count = 0;
foreach($repos as $repo) {
    if (filterRepo($repo["name"])) {
        continue;
    }

    if (!in_array($repo["name"], $ignore)
        && !in_array($repo["name"], $unfinished)
        && !in_array($repo["name"], $extern)
    ) {
        $content .= "[" . $repo["name"] . "](https://github.com/symcon/" . $repo["name"] . "/) | ";
        $content .= "[![Check Style](https://github.com/symcon/" . $repo["name"] . "/workflows/Check%20Style/badge.svg)](https://github.com/symcon/" . $repo["name"] . "/actions)" . " | ";
        $content .= "[![Run Tests](https://github.com/symcon/" . $repo["name"] . "/workflows/Run%20Tests/badge.svg)](https://github.com/symcon/" . $repo["name"] . "/actions)" . " | ";
        if (isInStore($repo["name"])) {
            $content .= " ✅";
        }
        $content .= " | ";
        if (hasURL($repo["name"])) {
            $content .= " ✅";
        }
        $content .= PHP_EOL;

        $count++;
    }
}

$content .= "Aktuelle Anzahl der PHP-Module: " . $count . PHP_EOL;
$content .= PHP_EOL;

$content .= "### Veraltete/Besondere PHP-Module" . PHP_EOL;
$content .= PHP_EOL;
$content .= "Name |" . PHP_EOL;
$content .= "---- |" . PHP_EOL;

foreach($repos as $repo) {
    if (filterRepo($repo["name"])) {
        continue;
    }

    if (in_array($repo["name"], $ignore)) {
        $content .= "[" . $repo["name"] . "](https://github.com/symcon/" . $repo["name"] . "/) |" . PHP_EOL;
    }
}
$content .= PHP_EOL;
$content .= PHP_EOL;

$content .= "### Externe PHP-Module" . PHP_EOL;
$content .= PHP_EOL;
$content .= "Name |" . PHP_EOL;
$content .= "---- |" . PHP_EOL;

foreach($repos as $repo) {
    if (filterRepo($repo["name"])) {
        continue;
    }

    if (in_array($repo["name"], $extern)) {
        $content .= "[" . $repo["name"] . "](https://github.com/symcon/" . $repo["name"] . "/) |" . PHP_EOL;
    }
}
$content .= PHP_EOL;
$content .= PHP_EOL;

$content .= "### Unvollständige PHP-Module" . PHP_EOL;
$content .= PHP_EOL;
$content .= "Name |" . PHP_EOL;
$content .= "---- |" . PHP_EOL;

foreach($repos as $repo) {
    if (filterRepo($repo["name"])) {
        continue;
    }

    if (in_array($repo["name"], $unfinished)) {
        $content .= "[" . $repo["name"] . "](https://github.com/symcon/" . $repo["name"] . "/) |" . PHP_EOL;
    }
}

file_put_contents("README.md", $content);

//Copy&Paste: https://www.php.net/manual/de/reserved.variables.httpresponseheader.php
function parseHeaders($headers)
{
    $head = array();
    foreach($headers as $k => $v) {
        $t = explode(':', $v, 2);
        if(isset($t[1])) {
            $head[ trim($t[0]) ] = trim($t[1]);
        } else {
            $head[] = $v;
            if(preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                $head['reponse_code'] = intval($out[1]);
            }
        }
    }
    return $head;
}
