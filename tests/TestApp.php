<?php
namespace Stack\Auth\Tests;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TestApp implements HttpKernelInterface {


    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if($request->getRequestUri() == "/") return new Response("public");
        return new Response("protected");
    }



}