<?php

namespace App\Controllers;

use App\App;

class HomeController extends App
{
    public function index($request, $response, $args){
        return $this->view->render($response, "home/index.twig");
    }

    public function list($request, $response, $args){
        return $this->view->render($response, "list/index.twig");
    }

    public function edit($request,$response,$args){
        return $this->view->render($response, "home/edit.twig", ["id" => $args['id']]);
    }

    public function infoInvoice($request, $response, $args){
        return $this->view->render($response, "home/view.twig", ["id" => $args['id']]);
    }
}