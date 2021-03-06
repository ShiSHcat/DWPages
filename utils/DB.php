<?php

namespace DWPages\Utils;

use function \Amp\call;

class DB
{
    public $pool;
    private $localh = false;

    public function __construct()
    {
        if ($_SERVER["argv"][1]??"" == "--run-on-localhost") {
            echo "Running without a db.\n";
            $this->localh = true;
        } else {
            $config = \Amp\Mysql\ConnectionConfig::fromString(
                "host=127.0.0.1 user=SECRET_USERNAME password=SECRET_PASSWORD db=dwpages"
            );
            $this->pool = \Amp\Mysql\pool($config);
        }
    }
    

    public function getJH($host)
    {
        return call(function ($host) {
            if (!$this->localh) {
                $stam = yield $this->pool->prepare("SELECT * FROM dwpages WHERE dname = :dname");
                $result = yield $stam->execute(['dname' => \str_replace(".SECRET_SITE_URL", "", $host)]);
                while (yield $result->advance()) {
                    $row = $result->getCurrent();
                    return $row["jhash"];
                }
                return false;
            }
            return "@shishcatpublic";
        }, $host);
    }
}
