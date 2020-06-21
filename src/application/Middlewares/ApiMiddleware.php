<?php

namespace App\Middlewares;
use App\App;

class ApiMiddleware extends App{
    public function __invoke($request, $response, $next){
        if(sizeof($request->getHeader("Authorization")) > 0)
        {
            $apiKey = $request->getHeader("Authorization")[0];

            $result = $this->db->table("authTokens")->where("key","=",$apiKey)->first();
            if(!is_null($result)){
             
                if(time() - strtotime($result->created_at) > $this->settings['token_expiry']){
                    return $response->withJson([
                        "status" => 401,
                        "message" => "Unauthorized"
                    ], 401);
                }
                else{
                    return $next($request, $response);
                }
            }
        }

        return $response->withJson([
            "status" => 401,
            "message" => "Unauthorized"
        ], 401);
    }
}