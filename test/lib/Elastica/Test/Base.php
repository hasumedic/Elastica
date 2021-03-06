<?php

namespace Elastica\Test;

use Elastica\Client;
use Elastica\Connection;
use Elastica\Index;

class Base extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $params Additional configuration params. Host and Port are already set
     * @param callback $callback
     * @return Client
     */
    protected function _getClient(array $params = array(), $callback = null)
    {
        $config = array(
            'host' => $this->_getHost(),
            'port' => $this->_getPort(),
        );

        $config = array_merge($config, $params);

        return new Client($config, $callback);
    }

    /**
     * @return string Host to es for elastica tests
     */
    protected function _getHost()
    {
        return getenv('ES_HOST') ?: Connection::DEFAULT_HOST;
    }

    /**
     * @return int Port to es for elastica tests
     */
    protected function _getPort()
    {
        return getenv('ES_PORT') ?: Connection::DEFAULT_PORT;
    }

    /**
     * @return string Proxy url string
     */
    protected function _getProxyUrl()
    {
        return "http://127.0.0.1:12345";
    }

    /**
     * @return string Proxy url string to proxy which returns 403
     */
    protected function _getProxyUrl403()
    {
        return "http://127.0.0.1:12346";
    }

    /**
     * @param  string          $name   Index name
     * @param  bool            $delete Delete index if it exists
     * @param  int             $shards Number of shards to create
     * @return \Elastica\Index
     */
    protected function _createIndex($name = null, $delete = true, $shards = 1)
    {
        if (is_null($name)) {
            $name = preg_replace('/[^a-z]/i', '', strtolower(get_called_class())).uniqid();
        }

        $client = $this->_getClient();
        $index = $client->getIndex('elastica_'.$name);
        $index->create(array('index' => array('number_of_shards' => $shards, 'number_of_replicas' => 0)), $delete);

        return $index;
    }

    protected function _waitForAllocation(Index $index)
    {
        do {
            $settings = $index->getStatus()->get();
            $allocated = true;
            foreach ($settings['shards'] as $shard) {
                if ($shard[0]['routing']['state'] != 'STARTED') {
                    $allocated = false;
                }
            }
        } while (!$allocated);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->_getClient()->getIndex('_all')->delete();
        $this->_getClient()->getIndex('_all')->clearCache();
    }
}
