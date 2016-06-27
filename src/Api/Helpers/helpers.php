<?php

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (! function_exists('bcrypt')) {
    /**
    * Hash the given value.
    *
    * @param  string  $value
    * @param  array   $options
    * @return string
    */
    function bcrypt($value, $options = [])
    {
        return app('hash')->make($value, $options);
    }
}
