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

function hasURL($name) {
    global $modules;
    foreach ($modules as $module) {
        if (!isset($module->url))
            continue;
        if (strpos($module->url, "/symcon/" . $name) !== false) {
            return true;
        }
    }
    return false;
}

function isInStore($name) {
    global $modules, $store;
    foreach ($modules as $module) {
        if ($module->name == $name) {
            return true;
        }
    }
    // Fallback if direct name matching did not work
    return in_array($name, $store);
}

$content = "### Übersicht aller PHP-Module und deren Check Status" . PHP_EOL;
$content .= PHP_EOL;
$content .= "Name | Style | Tests | Store | URL" . PHP_EOL;
$content .= "---- | ----- | ----- | ----- | ---" . PHP_EOL;

foreach($repos as $repo) {
    if (substr($repo["name"], 0, 4) == "Skin")
        continue;
    if (substr($repo["name"], 0, 5) == "Style")
        continue;
    if (substr($repo["name"], 0, 7) == "action-")
        continue;
    if (in_array($repo["name"], ["AndroidSDK", "Status"]))
        continue;

    $content .= "[" . $repo["name"] . "](https://github.com/symcon/" . $repo["name"] . "/) | ";
    if (in_array($repo["name"], $ignore)) {
        $content .= "N/A   | N/A   | N/A   | N/A" . PHP_EOL;
    } else {
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
    }
}

file_put_contents("README.md", $content);

//Copy&Paste: https://www.php.net/manual/de/reserved.variables.httpresponseheader.php
function parseHeaders( $headers )
{
    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ trim($t[0]) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['reponse_code'] = intval($out[1]);
        }
    }
    return $head;
}
