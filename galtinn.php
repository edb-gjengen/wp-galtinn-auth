<?php
/*
  Plugin Name: Galtinn Authentication
  Plugin URI: https://galtinn.neuf.no
  Description: Authenticate with username or email with galtinn.neuf.no
  Version: 0.1.0
  Author: EDB-gjengen
  Author URI: https://edb.technology
  License: MIT
 */

if (!defined('GALTINN_URL')) {
    define('GALTINN_URL', 'https://galtinn.neuf.no/api/me/basic/');
}

require_once 'GaltinnAuth.php';

new GaltinnAuth();
