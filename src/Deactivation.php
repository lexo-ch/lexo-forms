<?php

namespace LEXO\LF;

use const LEXO\LF\{
    CACHE_KEY
};

class Deactivation
{
    public static function run()
    {
        delete_transient(CACHE_KEY);
    }
}
