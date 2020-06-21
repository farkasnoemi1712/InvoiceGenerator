<?php
$app->get("/setup","SetupController:createDatabase")->setName("name");
$app->get("/", "HomeController:index")->setName("home");
$app->get("/edit/{id}","HomeController:edit")->setName("edit");
$app->get("/list", "HomeController:list")->setName("list");
$app->get('/view/{id}', "HomeController:infoInvoice")->setName('view');

$app->group("/account", function () use($app){
    $app->get("/login", "AccountController:login")->setName("login");
    $app->get("/register", "AccountController:register")->setName("register");
});

$app->group("/api",function () use($app){
    $app->group("/account",function () use($app){
        // parametrii: email, password, username, confirmPassword
        $app->post("/register", "ApiController:actionRegister")->setName("register");
        // email, password
        $app->post("/login", "ApiController:actionLogin")->setName("login");
    });
    $app->group("/invoice",function() use ($app){  
        // data, file-imaginea
        $app->post("/create","ApiController:actionCreateInvoice")->setName("invoice");
        $app->get("/all","ApiController:actionGetAllInvoices")->setName("invoices");
        // data, file-imaginea, id 
        $app->post("/edit/{id}","ApiController:actionEditInvoice")->setName("editInvoice");
        // id- invoice
        $app->post("/delete/{id}","ApiController:deleteInvoiceAction")->setName("deleteInvoiceAction");
    })->add(new \App\Middlewares\ApiMiddleware($app->getContainer()));
    // id invoice
    $app->get("/invoice/{id}","ApiController:actionGetInvoice")->setName("singleInvoice");
});