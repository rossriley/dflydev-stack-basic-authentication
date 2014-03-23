<?php
namespace Stack\Auth\Tests;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Stack\Auth\BasicAuthentication;



class ChallengeTest extends \PHPUnit_Framework_TestCase {


    public function testChallenge() {
        $response = $this->doRequest(Request::create("/protected","GET"));
        $this->assertNotEmpty($response->headers->get("www-authenticate"));
        $this->assertEquals($response->getStatusCode(), 401);
    }

    public function testNoChallenge() {
        $response = $this->doRequest(Request::create("/","GET"));
        $this->assertEmpty($response->headers->get("www-authenticate"));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testAuthentication() {
        $request = Request::create('GET','/protected',[],[],[], ['PHP_AUTH_USER' => 'test', 'PHP_AUTH_PW' => 'password']);
        $response = $this->doRequest($request);
        $this->assertEquals($response->getStatusCode(), 200);
    }


    protected function doRequest($request) {
        $app = new TestApp;
        $app = new BasicAuthentication($app, $this->firewall());
        return $app->handle($request);
    }

    protected function auth($username, $password) {
        if($username == "test" && $password =="password") return true;
        return false;
    }

    protected function firewall() {
        return [
            'firewall' => [['path' => '/protected']],
            'authenticator' => function($username, $password) { return $this->auth($username, $password); },
            'realm' => 'Requires authentication',
        ];
    }


}