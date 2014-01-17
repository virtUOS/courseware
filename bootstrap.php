<?php
    if (!class_exists('CSRFProtection')) {
        class CSRFProtection {
            public static function tokenTag() { return ''; }
        }
    }

    if (!class_exists('SkipLinks')) {
        class SkipLinks {
            public static function addIndex($name, $id) {}
            public static function addLink($name, $id) {}
        }
    }
    
    require_once 'vendor/trails/trails.php';
    require_once 'app/controllers/studip_controller.php';
#   require_once 'app/controllers/authenticated_controller.php';