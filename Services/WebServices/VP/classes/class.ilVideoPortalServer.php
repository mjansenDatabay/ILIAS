<?php
// fau: videoPortal - new class ilVideoPortalServer
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Video portal functions
 */
class ilVideoPortalServer extends Slim\App
{
    protected $token = "xxx";


    /**
     * ilRestServer constructor.
     * @param array $container
     */
    public function __construct($container = [])
    {
        parent::__construct($container);
    }

    /**
     * Init server / add handlers
     */
    public function init()
    {
        $this->get('/check/{user}/{type}/{id}', array($this, 'checkAccess'));
    }

    /**
     * Check Access to a video portal course or clip
     * @param Request  $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function checkAccess(Request $request, Response $response, array $args)
    {
        // Authentication
        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization != 'Bearer ' . $this->token) {
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(\Slim\Http\StatusCode::HTTP_UNAUTHORIZED)
                ->withJson('Authorization failed.');
            return $response;
        }


        $json = [
            'auth' => $authorization,
            'args' => $args,
            'access'=> true,
            'message_en' => 'Message en',
            'message_de' => 'Message de'
        ];

        $response = $response
            ->withStatus(\Slim\Http\StatusCode::HTTP_OK)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($json);

        return $response;
    }

}
