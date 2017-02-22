<?php

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\Http\HttpRequest;
use LizardsAndPumpkins\Util\Factory\ProjectFactory;

require_once '../vendor/autoload.php';

$request = HttpRequest::fromGlobalState(file_get_contents('php://input'));
$implementationSpecificFactory = new ProjectFactory();

$website = new DefaultWebFront($request, $implementationSpecificFactory);
$website->run();
