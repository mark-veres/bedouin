<?php

$map = json_decode(file_get_contents("map.json"));
$method = strtolower($_SERVER['REQUEST_METHOD']);
$url_parts = array_values(array_filter(
    explode('/', $_SERVER['REQUEST_URI']),
    function ($e) { return !empty($e); }
));

if (sizeof($url_parts) == 0) {
    $index = array_search("", array_column($map, "segment"));
    $obj = $map[$index];

    if ($obj->method != $method || $obj->method == "all") return;
    include "./routes/".$obj->file;
    exit();
}

if ($url_parts[0] == "static") {
    $filename = implode("/", $url_parts);
    header('Content-Type: '.mime_content_type($filename));
    readfile("./".$filename);
    exit();
}

$current = $map;
foreach ($url_parts as $i => $segment) {
    $dynamic_segments = array_values(array_filter($current, function ($e) {
        return str_starts_with($e->segment, "[") && str_ends_with($e->segment, "]");
    }));
    $matching_segments = array_values(array_filter($current, function ($e) use ($segment) {
        return $e->segment == $segment;
    }));
    $matches = (sizeof($dynamic_segments)!=0) ? $dynamic_segments : $matching_segments;

    if (sizeof($matches) == 0) {
        if (file_exists("./routes/404.php")) {
            die(include "./routes/404.php");
        }
        die("<h1>404 Page Not found</h1>");
    }

    if ($matches[0]->method != $method && $matches[0]->method != "all") die("wrong method");
    if (sizeof($url_parts)-1 == $i) {
        include "./routes/".$matches[0]->file;
    }

    $current = $matches[0]->children;
}