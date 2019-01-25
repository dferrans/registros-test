<?php 
//Cargando las librerias de Composer
//se instalo SLIM framework, GUZZLEHTTP, Respect-validator
//Librerias muy practicas para este tipo de implementacion.
require 'vendor/autoload.php';
//Cargando los Name spaces requeridos.
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
//Respect Validation
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

//SLIM aplication options
//application with errors on on DEV.
$app = new \Slim\App(
    [
     'settings' => [
         'displayErrorDetails' => true
     ]   
    ]);
//El endpoint Recibe los siguientes variables (GET)
//firstname, lastname, email, zipcode, phone.
   
$app->get('/crm-lead/firstname/{firstname}/lastname/{lastname}/email/{email}/zipcode/{zipcode}/phone/{phone}', function (Request $request, Response $response, Array $args){
//Validaciones Basicas Del servicio
   $uservalidation = v::key('firstname', v::alpha()->length(3,32))
                    ->key('lastname', v::alpha()->length(3,32))
                    ->key('email', v::email())
                    ->key('zipcode' , v::in( ['08456','09789','04536']) )
                    ->key('phone', v::phone());

    //Array con listado de  tiendas y su respectivo codigo. sirve
    //para saber las equivalencias de zipcodes Y LA RESPECTIVA TIENDA.
    $maptiendas = ['08456' => 'CO1234' ,'09789' => 'CO5678' , '04536' => 'ES3456'];
    //Try/catch que valida los parametros enviados, si esta ok, hace la solicitud al servicio web
    //de los contrario muestra en error con formato JSON al cliente que consume el servicio.
    try{
        $uservalidation->assert($args);

        //Este codigo solo se ejecuta si la valication de los campos NO genera ningun error.
        //se incluye la storeID a las variables
        $args['storeID'] = $maptiendas[$args['zipcode']];
        //se hace la solicitud POST usando GUZZLE.
        $client = new \GuzzleHttp\Client();
        //END POINT generado para esta prueba.
        //la consola se puede ubicar en 
        //https://test-df.free.beeceptor.com/console/test-df

        $request = $client->post('https://test-df.free.beeceptor.com', [
            'json' => $args
            ]);
            //Dependiendo de la respuesta, informar al cliente que CONSUME el servicio web.
            if($request->getStatusCode() == 200 || $request->getStatusCode() == 201 ){
                return $response->withJson(['data'=> 'ok', 'message' => 'Se envio informacion con exito'], 200);
            }else{
                return $response->withJson(['data'=> 'failed', 'message' => 'Error de conexion con el servidor'], 500);
            }
       
        //phpinfo();

    } catch( NestedValidationException $exception) {
        // API standar responses 400 Bad request https://developer.amazon.com/docs/amazon-drive/ad-restful-api-response-codes.html
        return $response->withJson($exception->getMessages(), 400);
        }
    
});

//Line to start Slim app
$app->run();

?>
