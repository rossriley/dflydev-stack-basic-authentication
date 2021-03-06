<?php

namespace Stack\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Dflydev\Stack\Firewall;
use Dflydev\Stack\WwwAuthenticateStackChallenge;

class BasicAuthentication implements HttpKernelInterface
{
    private $app;

    public function __construct(HttpKernelInterface $app, array $options = array())
    {
        $this->app = $app;

        if (!isset($options['authenticator'])) {
            throw new \InvalidArgumentException(
                "The 'authenticator' service must be set"
            );
        }

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }

    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // The challenge callback is called if a 401 response is detected that
        // has a "WWW-Authenticate: Stack" header. This is per the Stack
        // Authentication and Authorization proposals. It is passed the existing
        // response object.
        $challenge = function (Response $response) {
            $parts = ['Basic'];
            if (isset($this->realm)) {
                $parts[] = 'realm="'.$this->realm.'"';
            }

            $response->headers->set('WWW-Authenticate', implode(' ', $parts));

            return $response;
        };

        // The authenticate callback is called if the request has no Stack
        // authentication token but there is an authorization header. It is
        // passed an app we should delegate to (assuming we do not return
        // beforehand) and a boolean value indicating whether or not anonymous
        // requests should be allowed.
        $authenticate = function ($app, $anonymous) use ($request, $type, $catch, $challenge) {
            if (false === $username = $request->headers->get('PHP_AUTH_USER', false)) {
                if ($anonymous) {
                    // This is not a Basic Auth request but the firewall allows
                    // anonymous requests so we should wrap the application
                    // so that we might be able to challenge if authorization
                    // fails.
                    return (new WwwAuthenticateStackChallenge($app, $challenge))
                        ->handle($request, $type, $catch);
                }

                // Anonymous requests are not allowed so we should challenge
                // immediately.
                return call_user_func($challenge, (new Response)->setStatusCode(401));
            }

            $token = $this->authenticator->__invoke($username, $request->headers->get('PHP_AUTH_PW'));


            if (null === $token) {
                if ($anonymous) {
                    // Authentication faild but anonymous requests are allowed
                    // so we will pass this on. If authorization fails, we have
                    // wrapped the app in a challenge middleware that will let
                    // us challenge for basic auth.
                    return (new WwwAuthenticateStackChallenge($app, $challenge))
                        ->handle($request, $type, $catch);
                }

                // We should challenge immediately if anonymous requests are not
                // allowed.
                return call_user_func($challenge, (new Response)->setStatusCode(401));
            }

            $request->attributes->set('stack.authn.token', $token);

            return $app->handle($request, $type, $catch);
        };

        return (new Firewall($this->app, [
                'challenge' => $challenge,
                'authenticate' => $authenticate,
                'firewall' => $this->firewall,
            ]))
            ->handle($request, $type, $catch);
    }


}
