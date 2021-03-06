<?php

namespace DWPages\Utils;

use function \Amp\call;

class DWGWrapper
{
    private $client;
    protected $bslittt = 99999999999999;
    public function __construct($client)
    {
        $this->client = $client;
    }

    public function serveFile($file,$method="GET",$range=false)
    {
        return call(function ($file,$method="GET",$range=false) {
            $request = new \Amp\Http\Client\Request(\str_replace("https://dwgram.xyz", "http://127.0.0.1:1337", $file));
            $request->setBodySizeLimit($this->bslittt);
            $request->setProtocolVersions(["1.0","1.1"]);
            $request->setInactivityTimeout(0);
            $request->setTransferTimeout($this->bslittt); // 120 seconds
            if($range) $request->setHeader($range);
            $request->setMethod("GET");
            $resp = yield $this->client->request($request);
            $h = $resp->getHeaders();
            $b = new \Amp\Http\Server\Response($resp->getStatus(), ["content-type"=>$h["content-type"][0],"accept-ranges"=>"bytes"]);
            $b->setBody($resp->getBody());
            if (isset($h["content-length"][0])) {
                $b->setHeader("content-length", $h["content-length"][0]);
            }
            if (isset($h["content-range"][0])) {
                $b->setHeader("content-range", $h["content-range"][0]);
            }
            return $b;
        }, $file,$method,$range=false);
    }

    public function getMsgs($jh, $skip = 0)
    {
        return call(function ($jh, $skip) {
            return \json_decode(
                yield (
                    yield $this->client->request(
                        new \Amp\Http\Client\Request("http://127.0.0.1:1337/api/getchat?".\http_build_query(["joinchat"=>$jh,"dwpagesparse"=>"true"]))
                    )
                )->getBody()->buffer(),
                true
            );
        }, $jh, $skip);
    }
}
