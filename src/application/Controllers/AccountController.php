<?php

namespace App\Controllers;
use App\App;

class AccountController extends App
{
    public function login($request, $response, $args)
    {
        return $this->view->render($response, "account/login.twig");
    }

    public function register($request, $response, $args)
    {
        return $this->view->render($response, "account/register.twig");
    }
}