<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once '../GaltinnAuth.php';

define('GALTINN_URL', 'http://127.0.0.1:8000/api/me/basic/');

final class AuthTest extends TestCase {
    public function testCanBeCreatedFromValidEmailAddress(){
        $username = 'admin';
        $pasword = 'admin';
        $galtinn = new GaltinnAuth($run_wp_hooks=false);

        $res = $galtinn->get_galtinn_user($username, $pasword);

        $this->assertEquals(200, $res->status_code, var_export($res, true));
    }
}
