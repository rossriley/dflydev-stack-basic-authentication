<?php
namespace Stack\Auth\Tests;

use Symfony\Component\HttpFoundation\Request;
use Stack\Auth\BasicAuthentication;



class ChallengeTest extends \PHPUnit_Framework_TestCase
{


    public function testChallenge()
    {
        $response = $this->doRequest(Request::create("/protected","GET"));
        $this->assertNotEmpty($response->headers->get("www-authenticate"));
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testNoChallenge()
    {
        $response = $this->doRequest(Request::create("/","GET"));
        $this->assertEmpty($response->headers->get("www-authenticate"));
        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testAuthentication()
    {
        $request = Request::create('/protected','GET',[],[],[], ['PHP_AUTH_USER' => 'test', 'PHP_AUTH_PW' => 'password']);
        $response = $this->doRequest($request);
        $this->assertEquals("protected", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testBadAuthentication()
    {
        $request = Request::create('/protected','GET',[],[],[], ['PHP_AUTH_USER' => 'baduser', 'PHP_AUTH_PW' => 'wrongpassword']);
        $response = $this->doRequest($request);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertNotEquals("protected", $response->getContent());
    }

    public function testNoAuthenticatorFails()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $firewall = $this->firewall();
        $firewall["authenticator"] = null;
        $app = new Fixtures\TestApp;
        $app = new BasicAuthentication($app, $firewall);
        $response = $app->handle($request);
    }

    public function testAnonymous()
    {
        $firewall = $this->firewall();
        $firewall["firewall"][] = ["path"=>"/anon", "anonymous"=>true];
        $app = new Fixtures\TestApp;
        $app = new BasicAuthentication($app, $firewall);
        $response = $app->handle(Request::create("/anon","GET"));
        $this->assertEquals(200, $response->getStatusCode());
    }



    protected function doRequest($request)
    {
        $app = new Fixtures\TestApp;
        $app = new BasicAuthentication($app, $this->firewall());
        return $app->handle($request);
    }


    protected function firewall()
    {
        $auth = function($username, $password) {
            if($username == "test" && $password =="password") return "securetoken";
        };

        return [
            'firewall' => [['path' => '/protected']],
            'authenticator' => $auth,
            'realm' => 'Requires authentication',
        ];
    }


}