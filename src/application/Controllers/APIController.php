<?php
namespace App\Controllers;

use App\App;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Products;
use App\Models\AuthTokens;

class APIController extends App
{
    public function actionRegister($request, $response, $args){
        $parsedBody = $request->getParsedBody();

        if(!isset($parsedBody['username']) || !isset($parsedBody['email']) || 
        !isset($parsedBody['password']) || !isset($parsedBody['confirmPassword'])){
            return $response->withJson(["error_message" => "Nu sunt toate datele trimise",
                                        "status" => 400], 400);
        }

        $username = $parsedBody['username'];
        $email = $parsedBody['email'];
        $password = $parsedBody['password'];
        $confirmPassword = $parsedBody['confirmPassword'];

        if ($password!=$confirmPassword){
            return $response->withJson(["error_message" => "Cele 2 parole nu corespund",
                                        "status" => 400], 400);
        }

        $userFromDatabase = $this->db->table('user')->where('email','=',$email)->first();
        if (!is_null($userFromDatabase)){
            return $response->withJson(["error_message" => "Emailul este deja folosit",
                                        'status' => 400], 400);
        }

        $user = new User();
        $user->create([
            'username' => $username,
            'email' => $email, 
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);

        return $response->withJson(['message' => "Succes",
                                    'status' => 200], 200);
    }

    public function actionLogin($request, $response, $args){
        $parsedBody = $request->getParsedBody();

        if(!isset($parsedBody['email']) || !isset($parsedBody['password'])){
            return $response->withJson(["error_message" => "Nu sunt toate datele trimise",
                                        'status' => 400], 400);
        }

        $email = $parsedBody['email'];
        $password = $parsedBody['password'];
       
        $userFromDatabase = $this->db->table('user')->where('email','=',$email)->first();
        if (is_null($userFromDatabase)){
            return $response->withJson(['error_message'=> "emailul nu exista",
                                        'status' => 400], 400);
        }

        if (!password_verify ( $password , $userFromDatabase->password)){
            return $response->withJson(['error_message'=> "Parola nu a fost introdusa corect",
                                        'status' => 400] , 400);
        }

        $authTokens = new AuthTokens();
        $createdObject = $authTokens->create([
            'fk_user' => $userFromDatabase->pk_id,
            'key' => $this->staticMethods->getRandomKey(50)
        ]);

        return $response->withJson(
            [
                "key" => $createdObject->key,
                "expiry" => $this->settings['token_expiry']
            ],200);
    }

    public function actionCreateInvoice($request, $response, $args){
        $parsedBody = $request->getParsedBody();

        $apiKey = $request->getHeader("Authorization")[0];
        $idUser = $this->db->table("authTokens")->where("key","=",$apiKey)->first()->fk_user;
       
        $files = $request->getUploadedFiles();
        $dataObject = json_decode($parsedBody['data']);
         
        $image = "";
        if (isset($files['files']) && sizeof($files['files']) > 0){
            $path = $files['files'][0]->file;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $image = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $catreCine = $dataObject->catrecine;
        $date = $dataObject->date;
        $duedate = $dataObject->duedate;
        $finaltotal = $dataObject->finaltotal;
        $firma = $dataObject->firma;
        $notes = $dataObject->notes;
        $ordernumber = $dataObject->ordernumber;
        $subtotal = $dataObject->subtotal;
        $tax = $dataObject->tax;
        $terms = $dataObject->terms;

        $invoice = new Invoice();
        $createdInvoice = $invoice->create([
            'fk_user' => $idUser,
            'date' => $date,
            'due_date' => $duedate, 
            'user_info' => $firma,
            'client_info' => $catreCine, 
            'image' => $image, 
            'tax' => $tax, 
            'note' => $notes, 
            'terms' => $terms
        ]);

        $dateLinii = [];
        for ($i = 0; $i < sizeof($dataObject->dateLinii); $i++) {
            $dateLinii[] = $dataObject->dateLinii[$i];
        }

        for ($i = 0; $i < sizeof($dateLinii); $i++) {
            $quantity = $dateLinii[$i]->quantity;

            $products = new Products();
            $products->create([
                'fk_invoice' => $createdInvoice->id,
                'description' => $dateLinii[$i]->descriptionValue, 
                'quantity' => $dateLinii[$i]->quantity,
                'price' => $dateLinii[$i]->totalpriceValue
            ]);
        }
        return $response->withJson(['message' => "Dates in invoice table were created",
        'status' => 200, 'id' => $createdInvoice->id], 200);
    }

    
    public function actionGetAllInvoices($request, $response, $args){
        $apiKey = $request->getHeader("Authorization")[0];
        $idUser = $this->db->table("authTokens")->where("key","=",$apiKey)->first()->fk_user;
        $invoices = $this->db->table("invoice")->where("fk_user","=",$idUser)->get();

        $responseArray = [];

        foreach($invoices as $invoice){
            $products = $this->db->table("products")->where("fk_invoice","=",$invoice->pk_id)->get();

            $responseArray[] = [
                "pk_id" => $invoice->pk_id,
                "date" => $invoice->date,
                "due_date" => $invoice->due_date,
                "user_info" => $invoice->user_info,
                "client_info" => $invoice->client_info,
                "image" => $invoice->image,
                "tax" => $invoice->tax,
                "note" => $invoice->note,
                "terms" => $invoice->terms,
                "products" => $products
            ];
        }
        return $response->withJson($responseArray, 200);
    }  

    public function actionGetInvoice($request, $response, $args){

        $id = $args['id'];

        if(is_null($this->db->table('invoice')->where('pk_id','=',$id)->first())){
            return $response->withJson(['error_message' => 'Id-ul nu exista',
            'status' => '400']);
        }

        $invoiceObject = $this->db->table("invoice")->where("pk_id","=",$args['id'])->first();
        $products = $this->db->table("products")->where("fk_invoice","=",$args['id'])->get();
                
        $responseObj = [
                "pk_id" => $invoiceObject->pk_id,
                "date" => $invoiceObject->date,
                "due_date" => $invoiceObject->due_date,
                "user_info" => $invoiceObject->user_info,
                "client_info" => $invoiceObject->client_info,
                "image" => $invoiceObject->image,
                "tax" => $invoiceObject->tax,
                "note" => $invoiceObject->note,
                "terms" => $invoiceObject->terms,
                "products" => $products
        ];
        return $response->withJson($responseObj,200); 
    }

    public function actionEditInvoice($request, $response, $args){
        $id = $args['id'];
        $paredBody = $request->getParsedBody();

        if(is_null($this->db->table('invoice')->where('pk_id','=',$id)->first())){
            return $response->withJson(['error_message' => 'Id-ul nu exista',
            'status' => '400']);
        }

        $parsedBody = $request->getParsedBody();
        $apiKey = $request->getHeader("Authorization")[0];
        $idUser = $this->db->table("authTokens")->where("key","=",$apiKey)->first()->fk_user;
       
        $files = $request->getUploadedFiles();
        $dataObject = json_decode($parsedBody['data']);
         
        $image = "";
        if (isset($files['files']) && sizeof($files['files']) > 0){
            $path = $files['files'][0]->file;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $image = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        
        $catreCine = $dataObject->catrecine;
        $date = $dataObject->date;
        $duedate = $dataObject->duedate;
        $finaltotal = $dataObject->finaltotal;
        $firma = $dataObject->firma;
        $notes = $dataObject->notes;
        $ordernumber = $dataObject->ordernumber;
        $subtotal = $dataObject->subtotal;
        $tax = $dataObject->tax;
        $terms = $dataObject->terms;

        $this->db->table('invoice')->where('pk_id','=',$id)->update([
            'fk_user' => $idUser,
            'date' => $date,
            'due_date' => $duedate, 
            'user_info' => $firma,
            'client_info' => $catreCine, 
            'image' => $image, 
            'tax' => $tax, 
            'note' => $notes, 
            'terms' => $terms
        ]);

        $dateLinii = [];
        for ($i = 0; $i < sizeof($dataObject->dateLinii); $i++) {
            $dateLinii[] = $dataObject->dateLinii[$i];
        }

        $this->db->table('products')->where('fk_invoice','=',$id)->delete();
        
        for ($i = 0; $i < sizeof($dateLinii); $i++) {
            $quantity = $dateLinii[$i]->quantity;

            $products = new Products();
            $products->create([
                'fk_invoice' => $id,
                'description' => $dateLinii[$i]->descriptionValue, 
                'quantity' => $dateLinii[$i]->quantity,
                'price' => $dateLinii[$i]->totalpriceValue
            ]);
        }

        return $response->withJson(['message' => "Invoice was updated",
            'status' => 200], 200);
    }

    public function deleteInvoiceAction($request, $response, $args){
        $id = $args['id'];

        if(is_null($this->db->table('invoice')->where('pk_id','=',$id)->first())){
            return $response->withJson(['error_message' => 'Id-ul nu exista',
            'status' => '400']);
        }

        $this->db->table('products')->where('fk_invoice','=',$id)->delete();
        $this->db->table('invoice')->where('pk_id','=',$id)->delete();

        return $response->withJson(['message' => "Invoice was deleted",
            'status' => 200], 200);
    }

    public function testMethod($request, $response, $args){
        $mpdf = $this->pdf;
        $mpdf->WriteHTML('<h1>Hello world!</h1>');
        $mpdf->Output();
    }
}