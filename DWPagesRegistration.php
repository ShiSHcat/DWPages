<?php

namespace DWGramServer\API;

use Amp;
use \Amp\Http\Server\Response;
use danog;
use function \Amp\call;

class DWPagesRegistration
{
    private $pool;
    private $ips = [];
    private $client;
    public function __construct($pool, $client)
    {
        $this->pool = $pool;
        $this->client = $client;
    }

    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable=function ($request) {
            $body = yield $request->getBody()->buffer();
            if ($this->startsWith("{", $body)) {
                $pquery = \json_decode($body, true);
                if ($pquery==null) {
                    \parse_str($body, $pquery);
                }
            } else {
                \parse_str($request->getUri()->getQuery(), $pquery);
            }
            if ((($pquery["joinchat"]??"")=="")||(($pquery["subdom"]??"")=="")) {
                return new Response(200, ['content-type' => 'text/html',"Access-Control-Allow-Origin" => "*"], "<a href=\"https://SECRET_SITE_URL\">Back</a><br>Invalid data passed.");
            }
            $userip=$pquery["SECRET_HEADER"];
            $vdfe = $this->ips[$userip]??0;
            if ((!$userip)||!isset($this->ips[$userip])) {
                $this->ips[$userip] = \time();
            } elseif (15>((\time()-$this->ips[$userip])/60)) {
                return new Response(400, ['content-type' => 'text/html',"Access-Control-Allow-Origin" => "*"], "You are making too many requests!");
            } else {
                $this->ips[$userip] = \time();
            }

            $buffer = yield (
                yield $this->client->request(
                    new \Amp\Http\Client\Request("SECRET_DWGRAM_API_URL/api/getchat?".\http_build_query(["joinchat"=>$jh,"dwpagesparse"=>"true"]))
                )
            )->getBody()->buffer();

            $result = \json_decode($buffer, true);
            if ($result["ok"]) {
                $opts = [
                    "http" => [
                        "method" => "GET",
                        'ignore_errors' => true,
                        "header" => "X-Auth-Email: SECRET_CLOUDFLARE_EMAIL\r\n"  .
                                    "X-Auth-Key: SECRET_CLOUDFLARE_KEY\r\n"  .
                                    "Authorization: Bearer SECRET_CLOUDFLARE_AUTH"
                    ]
                ];

                $context = \stream_context_create($opts);

                $file = \json_decode(\file_get_contents('https://api.cloudflare.com/client/v4/zones/SECRET_CLOUDFLARE_DOMAINID_ZONE/dns_records?name='.\urlencode(\preg_replace("/[^a-zA-Z0-9]+/", "", $pquery["subdom"])).".SECRET_SITE_URL", false, $context), true);
                if (!empty($file["result"])) {
                    return new Response(200, ['content-type' => 'text/html',"Access-Control-Allow-Origin" => "*"], "<a href=\"https://SECRET_SITE_URL\">Back</a><br>Subdomain already taken.");
                }
                $opts = [
                        "http" => [
                            "method"  => "POST",
                            'ignore_errors' => true,
                            "header"  => "X-Auth-Email: SECRET_CLOUDFLARE_EMAIL\r\n"  .
                                         "X-Auth-Key: SECRET_CLOUDFLARE_KEY\r\n"  .
                                         "Authorization: Bearer SECRET_CLOUDFLARE_AUTH\r\n".
                                         "Content-type: application/json",
                            "content" => \json_encode(["type"=>"A","name"=>\urlencode(\preg_replace("/[^a-zA-Z0-9]+/", "", $pquery["subdom"])),"content"=>"SECRET_SERVER_IP","ttl"=>120,"priority"=>10,"proxied"=>true])
                        ]
                    ];
                $context = \stream_context_create($opts);
                $file = \json_decode(\file_get_contents('https://api.cloudflare.com/client/v4/zones/SECRET_CLOUDFLARE_DOMAINID_ZONE/dns_records', false, $context), true);
                $statement = yield $this->pool->prepare("INSERT INTO `dwpages` (`dname`, `jhash`) VALUES (:subdom, :jh)");
                $result = yield $statement->execute(["jh"=>($pquery["joinchat"]??""),'subdom' => \urlencode(\preg_replace("/[^a-zA-Z0-9]+/", "", $pquery["subdom"]))]);
                return new Response(200, ['content-type' => 'text/html',"Access-Control-Allow-Origin" => "*"], "Your site is now available <a href=\"https://".\urlencode(\preg_replace("/[^a-zA-Z0-9]+/", "", $pquery["subdom"])).".SECRET_SITE_URL\">here.</a><br>NOTE: DNS may take up to 24h to propagate.");
            }
            return new Response(200, ['content-type' => 'text/html',"Access-Control-Allow-Origin" => "*"], "<a href=\"https://SECRET_SITE_URL\">Back</a><br>Invalid data passed.");
        };
        return call($callable, $request);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
