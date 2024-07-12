<?php

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
// Repo => ModuleStore
$translation = [
    "VerbrauchZeitspanne" => "verbrauchinzeitspanne",
    "TTSAWSPolly" => "texttospeechawspolly",
    "SymconBackup" => "backupftpftpssftp",
    "SymconGraph" => "webgraph",
    "SymconSpotify" => "spotify"
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
        if($channel["channel"] == "stable") {
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
    return in_array($name, $url);
}

function isInStore($name)
{
    global $modules, $store;
    $module = getModule($name);
    if ($module !== false && $module->inStore) {
        return true;
    }

    // Fallback to manually checked modules
    return in_array($name, $store);
}

function getModule($name) : Module|false{
    global $modules, $translation;
    if(key_exists(strtolower($name),$modules)) {
        return $modules[strtolower($name)];
    }
    if(key_exists(strtolower($name),$translation)){
        return $modules[$translation[$name]];
    }
    foreach ($modules as $module) {
        if($module->libraryName == $name){
            return $module;
        }
    }
    var_dump("Module $name is not found");
    return false;
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
        var_dump("Repo:" . $repo["name"]);
        $content .= "[" . $repo["name"] . "](https://github.com/symcon/" . $repo["name"] . "/) | ";
        $content .= "[![Check Style](https://github.com/symcon/" . $repo["name"] . "/workflows/Check%20Style/badge.svg)](https://github.com/symcon/" . $repo["name"] . "/actions)" . " | ";
        $content .= "[![Run Tests](https://github.com/symcon/" . $repo["name"] . "/workflows/Run%20Tests/badge.svg)](https://github.com/symcon/" . $repo["name"] . "/actions)" . " | ";
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

var_dump($modules);

class Module
{
    public String $moduleName;
    public bool $inStore;
    public string $url;
    public int $urlStatus; // 0 = missing, 1 = documentation is redirect to github, 2 = ok
    public string $bundleID;
    public string $libraryName;

    public function __construct($module, $bundleID)
    {
        $replaceSpecialCharacters = function ($name): string {
            return preg_replace(["/[äÄ]/","/[üÜ]/","/[öÖ]/", "/ /","/-/", "/\(/", "/\)/", "/\//" ], ["ae", "ue", "oe", "","", "","", ""], $name);
        };

        $this->moduleName = $replaceSpecialCharacters(strtolower($module["name"]));
        $this->inStore = $module["channel"] == "stable" && $module["status"] == "released";
        $this->urlStatus = array_key_exists("documentation", $module) ? (strpos($module["documentation"], "/github/") !== false ? 1 : 2) : 0;
        if($this->urlStatus == 2) {
            $this->url = $module["documentation"];
        }
        $this->libraryName = json_decode($module["infoJSON"],true)["library"]["name"];
        $this->bundleID = $bundleID;
    }
}
