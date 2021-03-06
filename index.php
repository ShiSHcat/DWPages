<?php
include __DIR__."/../vendor/autoload.php";
include __DIR__."/DWPagesRegistration.php";
include __DIR__."/utils/DB.php";
include __DIR__."/utils/DWGWrapper.php";
use \Amp\ByteStream\ResourceOutputStream;
use \Amp\Http\Client\HttpClientBuilder;
use \Amp\Http\Server\HttpServer;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\RequestHandler\CallableRequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\StaticContent\DocumentRoot;
use \Amp\Http\Status;
use \Amp\Log\ConsoleFormatter;
use \Amp\Log\StreamHandler;
use \Amp\Socket\Server;
use Monolog\Logger;
$mdsettings = [];
$mdsettings['logger']['logger'] = \danog\MadelineProto\Logger::FILE_LOGGER;
$mdsettings['flood_timeout']['wait_if_lt'] = 30;
\Amp\Loop::run(function () {
    $sockets = [
        Server::listen("localhost:1338")
    ];
    $documentRoot = new DocumentRoot(__DIR__ . '/site');
    $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $client = HttpClientBuilder::buildDefault();
    $db = new \DWPages\Utils\DB();
    $dw = new \DWPages\Utils\DWGWrapper($client);
    $logger->pushHandler($logHandler);
    $dwp = new DWPagesRegistration($client,$db->pool);
    $server = new HttpServer($sockets, new CallableRequestHandler(function (Request $request) use ($documentRoot,$db,$dw,$client,$dwp) {
        $client->request(
            //check iplogger example in root (FPM)
            new \Amp\Http\Client\Request("SECRET_LOGGER?ssd=".$request->getHeader("cf-connecting-ip")."&data=".urlencode((string)$request->getUri()." ".(string)$request->getHost()." #getchatdwpages"))
        );
        $host = $request->getHeader("host");
        if (\in_array($host, ["www.SECRET_SITE_URL","SECRET_SITE_URL","test.SECRET_SITE_URL","SECRET_SITE_URL:1338"])) {
            if(\urldecode($request->getUri()->getPath()) == "dwpagesreg"){
                return $dwp->handleRequest($request);
            }
            return yield $documentRoot->handleRequest($request);
        }

        $joinhash = yield $db->getJH($host);
        if (!$joinhash) {
            return new Response(Status::OK, [
                "content-type" => "text/html; charset=utf-8"
            ], $host);
        }
        $counter = 0;
        $maxpolls = 3;
        $res = yield $dw->getMsgs($joinhash);
        if (!$res["ok"]) {
            return new Response(503, [
                "content-type" => "text/plain; charset=utf-8"
            ], "invalid chat link");
        }

        $filenames= $res["messages"];

        $pth = \urldecode($request->getUri()->getPath());
        if ($pth!=="/") {
            $e_ = \trim($pth, "/");
            if (isset($filenames[$e_])) {
                return yield $dw->serveFile($filenames[$e_],$request->getMethod(),$request->getHeader("range"));
            }
            if (isset($filenames[$e_.".html"])) {
                return yield $dw->serveFile($filenames[$e_.".html"],$request->getMethod(),$request->getHeader("range"));
            }
            if(strpos($e_, '.') !== false){
                $withoutExt = substr($e_, 0 , (strrpos($e_, ".")));
                if (isset($filenames[$withoutExt])) {
                    return yield $dw->serveFile($filenames[$withoutExt],$request->getMethod(),$request->getHeader("range"));
                }
            }
            return new Response(404, [
                    "content-type" => "text/html; charset=utf-8"
                ], "404");
        } elseif ($filenames["index.html"]??false) {
            return yield $dw->serveFile($filenames["index.html"]);
        }
        $files__ = [];
        foreach ($filenames as $kk=>$vv_) {
            $files__[] = "---> <a href=\"https://".$host."/".\urlencode($kk)."\">".\htmlspecialchars($kk)."</a>";
        }
        return new Response(Status::OK, [
        "content-type" => "text/html; charset=utf-8"
        ], \implode("<br>", $files__));
    }), $logger);

    yield $server->start();
    $documentRoot->onStart($server);
});
