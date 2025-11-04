<?php

namespace LEXO\LF\Core\Traits;

trait Helpers
{

    public static function getClassName($classname)
    {
        if ($name = strrpos($classname, '\\')) {
            return substr($classname, $name + 1);
        };

        return $name;
    }

    public static function setStatus404()
    {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    public static function printr(mixed $data): string
    {
        return "<pre>" . \print_r($data, true) . "</pre>";
    }

    /**
     * Get files from directory (excludes subdirectories)
     *
     * @param string $directory Directory path
     * @return array Array of filenames
     */
    public static function getFilesFromdirectory($directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $assets = array_values(array_diff(scandir($directory), array('..', '.')));

        return array_filter($assets, function ($item) use ($directory) {
            return !is_dir(trailingslashit($directory) . $item);
        });
    }
}
