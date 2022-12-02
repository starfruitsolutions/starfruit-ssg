<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/preview/{domain}/[{path: .*}]', function (Request $request, Response $response, array $args) {
    // if no path use index
    if(!isset($args['path'])) {
        $args['path'] = 'index.html';
    }

    $data= $this->get('dataSource')->getData();

    $template = $this->get('template', $data);      
    $templateFile = $template->getFile($args['path']);    
    $content=$templateFile->render();

    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', $templateFile->getMimeType());
});


$app->get('/deploy/{domain}[/]', function (Request $request, Response $response, array $args) {

    $data = $this->get('dataSource')->getData();
    $this->get('template', $data)->export($args['domain']);

    $response->getBody()->write('complete');
    return $response;
});

$app->get('/schema/{domain}[/]', function (Request $request, Response $response, array $args) {

    $schema = $this->get('template', ['template'=>'test'])->getSchema();

    $response->getBody()->write(json_encode($schema));
    return $response;
});