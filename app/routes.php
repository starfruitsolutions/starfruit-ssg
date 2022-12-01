<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/preview/{domain}/[{path: .*}]', function (Request $request, Response $response, array $args) {
    // if no path use index
    if(!isset($args['path'])) {
        $args['path'] = 'index.html';
    }

    $data= $this->get('dataSource')->getData();

    $template = $this->get('template', "templates/{$data['template']}/{$args['path']}");
    #if text run it through templating
    if($template->isText()) {     
        $content = $template->render($data);
    }
    else {
        $content = $template->getContent();
    }

    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', $template->getMimeType());
});


$app->get('/deploy/{domain}[/]', function (Request $request, Response $response, array $args) {

    $data = $this->get('dataSource')->getData();
    // iterate through template files recursively, render, and write to exports folder
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("templates/{$data['template']}/"));

    foreach ($iterator as $file){
        if (!$file->isDir()){
            //make the containing directories if they dont exist
            if(!is_dir("exports/{$args['domain']}/{$iterator->getSubPath()}")){
                mkdir("exports/{$args['domain']}/{$iterator->getSubPath()}", 0777, true);// make recursive directory
            }

            //file subpath
            $filePath = $iterator->getSubPathName();

            //render template
            $template = $this->get('template', "templates/{$data['template']}/{$filePath}");
            if($template->isText()) {
                $content = $template->render($data);
            }
            else {
                $content = $template->getContent();
            }

            // write to file
            file_put_contents("exports/{$args['domain']}/{$filePath}", $content);
        }
    }

    $response->getBody()->write('complete');
    return $response;
});