<?php

/**
 * Fired during plugin deactivation.
 */
class Plugin_Pagfort_Deactivator {

    /**
     * Clean option config
     */
    public static function deactivate() {
        flush_rewrite_rules(true);
    }

}
