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
    'color'=> '#3b065e',
    'test'=> 'Your business needs the power of technology. We build solutions that help you streamline your business and engage more customers. Get on the front line of the digital experience.'
];
$container->add('source', function () use ($source) {
    return $source;
});

$app->group('/preview', function (RouteCollectorProxy $group) {
    $group->group('/{domain}/', function (RouteCollectorProxy $group) {
        $group->get('', function (Request $request, Response $response, array $args) {
            
            $template = $this->get('template', 'templates/test/index.html');            
            $source= $this->get('source');
            $handlebars = $this->get('handlebars');
            $content = $handlebars->render($template->getContent(), $source);

            $response->getBody()->write($content);
            return $response->withHeader('Content-Type', 'text/html');
        });
        $group->get('{path: .*}', function (Request $request, Response $response, array $args) {
            $template = $this->get('template', "templates/test/{$args['path']}");
            #if text run it through templating
            if($template->isText()) {
                $handlebars = $this->get('handlebars');
                $source= $this->get('source');
                $content = $handlebars->render($template->getContent(), $source);
            }
            else {
                $content = $template->getContent();
            }

            $response->getBody()->write($content);
            return $response->withHeader('Content-Type', $template->getMimeType());
        });
    });
});

$app->group('/deploy/{domain}', function (RouteCollectorProxy $group) {

});
$app->run();
