<?php
namespace App\Interfaces;

use Illuminate\Http\Request;

interface AuthInterface
{
    /**
     * get a JWT via given credentials.
     *
     * @method POST api/auth/login
     * @param \Illuminate\Http\Request  $request
     * @access public
     */
    public function login(Request $request);

    public function me();

    public function logout();

    public function refresh();
}
