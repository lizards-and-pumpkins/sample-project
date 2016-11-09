<?php

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\Http\HttpRequest;
use LizardsAndPumpkins\Util\Factory\DemoProjectFactory;

require_once '../vendor/autoload.php';

$request = HttpRequest::fromGlobalState(file_get_contents('php://input'));
$implementationSpecificFactory = new DemoProjectFactory();

$website = new DefaultWebFront($request, $implementationSpecificFactory);
$website->run();
