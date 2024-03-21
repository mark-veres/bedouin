<?php

$objects = new RecursiveTreeIterator(
    new RecursiveDirectoryIterator(realpath('./routes'), RecursiveDirectoryIterator::SKIP_DOTS)
);

$routes = [];
$data = array();

foreach($objects as $name => $object) {
    if (is_dir($name)) continue;

    // Remove path part that won't be in the final URL
    $file_path = substr($name, strlen(realpath(__DIR__."/routes"))+1);

    // Extract HTTP method from filename
    // ex: index.get.php => GET method
    // ex: index.post.php => POST method
    $matches = [];
    $exp = "/(.*).(get|post|put|patch|delete|head|connect|option|trace).php/";
    preg_match($exp, $file_path, $matches);
    $method = $matches[2] ?? "all";

    // Remove the HTTP method and file extension from the filename, if it exists.
    $path = preg_replace("/\.(get|post|put|patch|delete|head|connect|option|trace)(?=\.php).php/", "", $file_path);
    $path = preg_replace("/.php/", "", $path);
    $path = preg_replace("/index$/", "", $path);
    
    $parts = array_filter(explode("/", $path), function ($e) {
        return $e != "";
    });

    array_push($routes, [$parts, $method, $file_path]);
}

uasort($routes, function($a, $b) {
    return -strcmp(implode("/", $a[0]), implode("/", $b[0]));
});

foreach ($routes as [$route, $method, $file_path]) {
    $current = &$data;

    if (sizeof($route) == 0) {
        array_push($data, [
            "segment" => "",
            "method" => $method,
            "file" => $file_path,
            "children" => []
        ]);
    }

    foreach ($route as $i => $segment) {
        $matching_children = array_filter($current,
            function($e) use ($segment) { return $e["segment"] == $segment; });
        if (sizeof($matching_children) == 0) {
            $array_length = array_push($current, [
                "segment" => $segment,
                "method" => (sizeof($route)-1 == $i) ? $method : "all",
                "file" => (sizeof($route)-1 == $i) ? $file_path : "",
                "children" => []
            ]);
            $current = &$current[$array_length-1]["children"];
            continue;
        }
        $index = array_search($segment, array_column($current, "segment"));
        $current[$index]["file"] = $file_path;
        $current[$index]["method"] = $method;
        $current = &$data[$index]["children"];
    }
}

$f = fopen("map.json", "w");
fwrite($f, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
fclose($f);