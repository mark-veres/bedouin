<?php

namespace Bedouin;

class Router {
    public $map;
    public $file_404;
    public $params = [];

    public function loadMap($path) {
        if (!file_exists($path)) {
            throw new \Error("map file not found");
        }
        $data = json_decode(file_get_contents($path));
        $this->map = $data->map ?? [];
        $this->file_404 = $data->file_404 ?? "";
    }

    public function currentRoute() {
        $method = strtolower($_SERVER["REQUEST_METHOD"]);
        $url_parts = array_values(array_filter(
            explode('/', $_SERVER['REQUEST_URI']),
            function ($e) { return !empty($e); }
        ));

        array_unshift($url_parts, "");

        if ($url_parts[0] == "static") {
            $filename = implode("/", $url_parts);
            if (!file_exists("./".$filename))
                die("file was not found");

            header('Content-Type: '.mime_content_type($filename));
            readfile("./".$filename);
            exit();
        }

        $current = $this->map;
        foreach ($url_parts as $i => $segment) {
            $dynamic_segments = array_values(array_filter($current, function ($e) {
                return str_starts_with($e->segment, "[") && str_ends_with($e->segment, "]");
            }));
            $matching_segments = array_values(array_filter($current, function ($e) use ($segment) {
                return $e->segment == $segment;
            }));
            $matches = (sizeof($dynamic_segments)!=0) ? $dynamic_segments : $matching_segments;

            if (sizeof($dynamic_segments)!=0) {
                $param_name = substr($matches[0]->segment, 1, -1);
                $this->params[$param_name] = $segment;
            }
        
            if (sizeof($matches) == 0) {
                if (file_exists($this->file_404)) {
                    die(include $this->file_404);
                }
                die("<h1>404 Page Not found</h1>");
            }
        
            if ($matches[0]->method != $method && $matches[0]->method != "all") die("wrong method");
            if (sizeof($url_parts)-1 == $i) {
                return $matches[0];
            }
        
            $current = $matches[0]->children;
        }
    }
}