<?php

/**
 * This generated an overview of the current modules
 * The result table has:
 * -    the name of the module with the link to the git repository
 * -    the status of the action Style
 * -    the status of the action Tests
 * -    a checkmark if the module is in the module store
 * -    a mark if the module has a documentation link
 *
 * The base data are getting from the git api (https://api.github.com/user/14051084/repos?per_page=100)
 * to compare to the store modules use the api from us (https://api.symcon.de/store/dump?language=de)
 */

/**
 * Git Api = git api
 * Store Api = our api
 * Name: Git Api [name]
 * Repo Link: Git Api [html_url]
 * Style / Test Status: Workflow badge
 * Store Checkmark: in_array(name, Store Api[modules][name])
 * URL Mark: if module is in store and the link direct to page set a checkmark, else if the link directed to the git a red dot, else nothing
 */

$ignore = [
    "SymconBC",
    "SymconBroken",
    "SymconHelper",
    "SymconIncompatible",
    "SymconMisc",
    "SymconTest",
    "SymconWebinar",
    "WeidmannEmlog",
    "re",
    "baresip",
    "SymconStubs"
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
// Module has an documentation URL // Necessary for beta modules
$url = [
    "site" => [
        "StromAbrechnungsModul",
        "VerbrauchsAlarm",
        "VerbrauchInKategorie",
        "VirtuelleGeraete",
    ],
    "git" => [
        "Tronity",
    ]
];
// Repo => ModuleStore
$translation = [
    "verbrauchzeitspanne" => "verbrauchinzeitspanne",
    "ttsawspolly" => "texttospeechawspolly",
    "symconbackup" => "backupftpftpssftp",
    "symcongraph" => "webgraph",
    "symconspotify" => "spotify",
    "symconreport" => "reportmodul", 
];

//get the github repos
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

// get the modules from the dump endpoint
$modules = json_decode(file_get_contents("https://api-dev.symcon.de/store/dump?language=de"), true)["modules"];
$ourModules = array_filter($modules, function ($key): bool {
    return strpos($key, "de.symcon") === 0;
}, ARRAY_FILTER_USE_KEY);
$modules = [];
foreach ($ourModules as $bundleID => $module) {
    foreach ($module as $channel) {
        if($channel["channel"] === "stable") {
            $moduleInstance = new Module($channel, $bundleID);
            $modules[$moduleInstance->moduleName] = $moduleInstance;
        }
    }
}

function hasURL($name)
{
    global $modules, $url;
    $module = getModule($name);
    if($module !== false) {
        return $module->urlStatus;
    }
    // Fallback to manually checked modules
    return in_array($name, $url["site"]) ? 2 : (in_array($name, $url["git"]) ? 1 : 0);
}

function isInStore($name): bool
{
    return getModule($name) !== false;
}

function getModule($name): Module|false
{
    global $modules, $translation;
    
    $moduleName = replaceSpecialCharacters(strtolower($name));
    if(key_exists($moduleName, $modules)) {
        return $modules[$moduleName];
    }
    if(key_exists($moduleName, $translation)) {
        return $modules[$translation[$moduleName]];
    }
    foreach ($modules as $module) {
        if($module->libraryName == $name) {
            return $module;
        }
    }
    return false;
}

function filterRepo($repo): bool
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

function replaceSpecialCharacters(string $input): string|false
{
    // return preg_replace(["/[äÄ]/","/[üÜ]/","/[öÖ]/", "/ /","/-/", "/\(/", "/\)/", "/\//" ], ["ae", "ue", "oe", "","", "","", ""], $input)
    $input = str_replace(" ", "", $input);
    $input = str_replace("-", "", $input);
    $input = str_replace(")", "", $input);
    $input = str_replace("(", "", $input);
    $input = str_replace("/", "", $input);
    $input = strtolower($input);
    $input = str_replace("ä", "ae", $input);
    $input = str_replace("ü", "ue", $input);
    $input = str_replace("ö", "oe", $input);
    return $input;
}

//Result table
$content = "### Übersicht aller PHP-Module der Symcon GmbH und deren Check Status" . PHP_EOL;
$content .= PHP_EOL;
$content .= "Name | Style | Tests | Store | URL" . PHP_EOL;
$content .= "---- | ----- | ----- | ----- | ---" . PHP_EOL;

$count = 0;
foreach($repos as $repo) {
    if (filterRepo($repo["name"])) {
        continue;
    }

    if (in_array($repo["name"], $ignore)
        || in_array($repo["name"], $unfinished)
        || in_array($repo["name"], $extern)
    ) {
        continue;
    }

    $content .= "[" . $repo["name"] . "](" . $repo["html_url"] . "/) | ";
    $content .= "[![Check Style](" . $repo["html_url"] . "/workflows/Check%20Style/badge.svg)](" . $repo["html_url"] . "/actions)" . " | ";
    $content .= "[![Run Tests](" . $repo["html_url"] . "/workflows/Run%20Tests/badge.svg)](" . $repo["html_url"] . "/actions)" . " | ";


    if (isInStore($repo["name"])) {
        $content .= " ✅";
    }
    $content .= " | ";

    $content .= match (hasURL($repo["name"])) {
        0 => "",
        1 => "🟠",
        2 => "✅",
        default => "",
    };

    $content .= PHP_EOL;
    $count++;
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
        $content .= "[" . $repo["name"] . "](" . $repo["html_url"] . "/) |" . PHP_EOL;
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
        $content .= "[" . $repo["name"] . "](" . $repo["html_url"] . "/) |" . PHP_EOL;
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
        $content .= "[" . $repo["name"] . "](" . $repo["html_url"] . "/) |" . PHP_EOL;
    }
}

file_put_contents("README.md", $content);

class Module
{
    public String $moduleName;
    public string $url;
    public int $urlStatus; // 0 = missing, 1 = documentation is redirect to github, 2 = ok
    public string $bundleID;
    public string $libraryName;

    public function __construct($module, $bundleID)
    {
        $replaceSpecialCharacters = function ($name): string {
            $input = str_replace(" ", "", $name);
            $input = str_replace("-", "", $input);
            $input = str_replace(")", "", $input);
            $input = str_replace("(", "", $input);
            $input = str_replace("/", "", $input);
            $input = strtolower($input);
            $input = str_replace(["ä", "Ä"], "ae", $input);
            $input = str_replace(["ü", "Ü"], "ue", $input);
            $input = str_replace(["ö", "Ö"], "oe", $input);
            return $input;
        };

        $this->moduleName = $replaceSpecialCharacters(strtolower($module["name"]));
        $this->urlStatus = array_key_exists("documentation", $module) ? (strpos($module["documentation"], "/github") !== false ? 1 : 2) : 0;
        if($this->urlStatus == 2) {
            $this->url = $module["documentation"];
        }
        $this->libraryName = json_decode($module["infoJSON"], true)["library"]["name"];
        $this->bundleID = $bundleID;
    }
}
