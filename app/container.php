<?php
use Slim\Factory\AppFactory;
use Handlebars\Handlebars;
use app\utils\container;
use app\utils\template;
use app\utils\datasource;

// create container
$container = new Container();

$container->add('template', function ($path) {
    return new Template($path);
});

$handlebars = new Handlebars();
$container->add('handlebars', function () use ($handlebars) {
    return $handlebars;
});

$dataSource = new DataSource();
$dataSource->setData([
    'domain'=> 'test',
    'template'=> 'test',
    'color'=> '#3b065e',
    'test'=> 'Your business needs the power of technology. We build solutions that help you streamline your business and engage more customers. Get on the front line of the digital experience.'
]);
$container->add('dataSource', function () use ($dataSource) {
    return $dataSource;
});

AppFactory::setContainer($container);