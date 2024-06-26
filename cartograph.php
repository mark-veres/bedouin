<?php

namespace Bedouin;

class Cartograph {
    public $route_folders = ["./routes"];
    public $root_dir;
    private $file_404;

    /**
     * Generates a list of files that
     * then get turned into routes.
     */
    public function listFiles($path) {
        $files = array_keys(iterator_to_array(new \RecursiveTreeIterator(
            new \RecursiveDirectoryIterator(realpath($path), \RecursiveDirectoryIterator::SKIP_DOTS)
        )));
        sort($files);
        return $files;
    }

    public function generateRoutes($files=[], $route_folder) {
        // - array_values reindexes the elements.
        // - array_filter removes empty elements
        // when no callback function is given.
        // - array map generates the routes
        return array_values(array_filter(array_map(function ($e) use ($route_folder) {
            return $this->filePathToRoute($e, $route_folder);
        }, $files)));
    }

    // This function generates individual routes
    // based on the file path. If the return value
    // is FALSE, then the route will be discarded.
    public function filePathToRoute($file_path, $route_folder) {
        if (is_dir($file_path)) return FALSE;

        // Remove path part that won't be in the final URL
        $rel_file_path = substr($file_path, strlen(realpath($this->root_dir))+1);
        $rel_path = substr($file_path, strlen(realpath($route_folder))+1);

        // Extract HTTP method from filename
        // ex: index.get.php => GET method
        // ex: index.post.php => POST method
        $matches = [];
        $exp = "/(.*).(get|post|put|patch|delete|head|connect|option|trace).php/";
        preg_match($exp, $rel_path, $matches);
        $method = $matches[2] ?? "all";

        // Remove the HTTP method and file extension from the filename, if it exists.
        $path = preg_replace("/\.(get|post|put|patch|delete|head|connect|option|trace)(?=\.php).php/", "", $rel_path);
        $path = preg_replace("/.php/", "", $path);
        $path = preg_replace("/index$/", "", $path);

        // Check if file is middleware
        $is_middleware = preg_match("/(.*).mw/", $path) ? TRUE : FALSE;

        if ($path == "404") {
            $this->file_404 = $file_path;
            return FALSE;
        }

        $parts = array_filter(explode("/", $path));
        if ($is_middleware) array_pop($parts);

        array_unshift($parts, "");

        return [
            "parts" => $parts,
            "method" => $method,
            "file_path" => $rel_file_path,
            "is_middleware" => $is_middleware
        ];
    }

    public function generateMap() {
        $data = [];
        $merged_routes = [];
        foreach ($this->route_folders as $route_folder) {
            $files = $this->listFiles($route_folder);
            $routes = $this->generateRoutes($files, $route_folder);
            $merged_routes = array_merge($merged_routes, $routes);
        }

        foreach($merged_routes as $route) {
            [$parts, $method, $file_path, $is_middleware] = array_values($route);

            $current = &$data;

            foreach ($parts as $i => $segment) {
                $matching_children = array_values(array_filter($current, function($e) use ($segment) {
                    return $e["segment"] == $segment;
                }));

                if (sizeof($matching_children) == 0) {
                    $array_length = array_push($current, [
                        "segment" => $segment,
                        "method" => (sizeof($parts)-1 == $i) ? $method : "all",
                        "file" => (sizeof($parts)-1 == $i) ? $file_path : "",
                        "children" => [],
                        "middleware" => []
                    ]);
                    if ($is_middleware) array_push($current[$array_length-1]["middleware"], [
                        "method" => $method,
                        "file" => $file_path
                    ]);
                    $current = &$current[$array_length-1]["children"];
                    continue;
                }
                $index = array_search($segment, array_column($current, "segment"));
                if (sizeof($parts)-1 == $i) {
                    $current[$index]["file"] = $file_path;
                    $current[$index]["method"] = $method;
                    if ($is_middleware) array_push($current[$index]["middleware"], [
                        "method" => $method,
                        "file" => $file_path
                    ]);
                }
                $current = &$current[$index]["children"];
            }
        }

        return $data;
    }

    // Writes the map data to a JSON file
    public function printMap($path) {
        $data = [
            "map" => $this->generateMap(),
            "file_404" => $this->file_404
        ];
        $f = fopen($path, "w");
        fwrite($f, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        fclose($f);
    }
}