<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use app\utils\container;

require __DIR__ . '/vendor/autoload.php';

class Template {
    public $path;
    public $content;

    function __construct($path) {
        $this->path = $path;
        $this->content = file_get_contents($path);
    }

    function getContent(){
        return $this->content;
    }

    function getExtension($path) {
        // will get text mimetypes wrong
        $fileNameParts = explode('.', $path);
        return end($fileNameParts);
        
    }
    
    function getMimeType() {
        $extension = $this->getExtension($this->path);
        // will get text mimetypes wrong
        if ($extension == 'js' || $extension == 'css'){
            return "text/{$extension}";
        }
        // fallback for everything else
        return mime_content_type($this->path);
    }

    function isText(){
        return explode('/', $this->getMimeType())[0] == 'text';
    }
}

// Create Container using PHP-DI
$container = new Container();
Slim\Factory\AppFactory::setContainer($container);

$app = AppFactory::create();


$container->add('template', function ($path) {
    return new Template($path);
});

$handlebars = new Handlebars\Handlebars();
$container->add('handlebars', function () use ($handlebars) {
    return $handlebars;
});

$source = [
    'domain'=> 'test',
    'template'=> 'test3',
    'color'=> '#3b065e',
    'test'=> 'Your business needs the power of technology. We build solutions that help you streamline your business and engage more customers. Get on the front line of the digital experience.'
];
$container->add('source', function () use ($source) {
    return $source;
});

$app->get('/preview/{domain}/[{path: .*}]', function (Request $request, Response $response, array $args) {
    // if no path use index
    if(!isset($args['path'])) {
        $args['path'] = 'index.html';
    }

    $source= $this->get('source');

    $template = $this->get('template', "templates/{$source['template']}/{$args['path']}");
    #if text run it through templating
    if($template->isText()) {
        $handlebars = $this->get('handlebars');        
        $content = $handlebars->render($template->getContent(), $source);
    }
    else {
        $content = $template->getContent();
    }

    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', $template->getMimeType());
});


$app->get('/deploy/{domain}[/]', function (Request $request, Response $response, array $args) {

    $source= $this->get('source');
    // iterate through template files recursively, render, and write to exports folder
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("templates/{$source['template']}/"));

    foreach ($iterator as $file){
        if (!$file->isDir()){
            //make the containing directories if they dont exist
            if(!is_dir("exports/{$args['domain']}/{$iterator->getSubPath()}")){
                mkdir("exports/{$args['domain']}/{$iterator->getSubPath()}", 0777, true);// make recursive directory
            }

            //file subpath
            $filePath = $iterator->getSubPathName();

            //render template
            $template = $this->get('template', "templates/{$source['template']}/{$filePath}");
            if($template->isText()) {
                $handlebars = $this->get('handlebars');                
                $content = $handlebars->render($template->getContent(), $source);
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
$app->run();
