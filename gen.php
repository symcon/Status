<?

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Awesome-PHP\r\n"
    ]
];

$context = stream_context_create($opts);

$repos = [];
for($i = 1; $i <= 2; $i++) {
    $repos = array_merge($repos, json_decode(file_get_contents("https://api.github.com/user/14051084/repos?page=" . $i, false, $context), true));
}

$width = 25;

$content = "### Übersicht aller PHP-Module und deren Check Status" . PHP_EOL;
$content .= PHP_EOL;
$content .= str_pad("Name", $width) . " | Style | Tests" . PHP_EOL;
$content .= str_pad("", $width, "-") . " | ----- | -----" . PHP_EOL;

foreach($repos as $repo) {
    if (substr($repo["name"], 0, 4) == "Skin")
        continue;
    if (substr($repo["name"], 0, 5) == "Style")
        continue;

    if (in_array($repo["name"], ["AndroidSDK", "Status"]))
        continue;

    if (in_array($repo["name"], ["SymconBC", "SymconBRELAG", "SymconBroken", "SymconHelper", "SymconIncompatible", "SymconLJ", "SymconMisc", "SymconTest", "SymconWebinar", "WeidmannEmlog"])) {
        $content .= str_pad($repo["name"], $width) . " | N/A   | N/A" . PHP_EOL;
    } else {
        $content .= str_pad($repo["name"], $width) . " | ";
        $content .= "[![Check Style](https://github.com/symcon/" . $repo["name"] . "/workflows/Check%20Style/badge.svg)](https://github.com/symcon/" . $repo["name"] . "/actions)" . " | ";
        $content .= "[![Run Tests](https://github.com/symcon/" . $repo["name"] . "/workflows/Run%20Tests/badge.svg)](https://github.com/symcon/" . $repo["name"] . "/actions)" . PHP_EOL;
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