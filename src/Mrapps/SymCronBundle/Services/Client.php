<?php

namespace Mrapps\SymCronBundle\Services;

use Guzzle\Http\Client as GuzzleClient;
use Monolog\Logger;
use Mrapps\SymCronBundle\Entity\Task;

class Client
{
    private $httpClient;

    private $logger;

    private $baseUrl;

    public function __construct(
        GuzzleClient $client,
        Logger $logger,
        $baseUrl
    )
    {
        $this->httpClient = $client;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl;
    }

    public function runTask(Task $task)
    {
        $url = $task->getUrl();

        $task->setStarted();

        if (0 === strpos($url, 'http')) {
            $fullUrl = $url;
        } else {
            $fullUrl = $this->baseUrl . $url;
        }

        try {
            switch ($task->getMethod()) {
                case "POST":
                    $request = $this->httpClient->post($fullUrl);
                    break;
                case "GET":
                default:
                    $request = $this->httpClient->get($fullUrl);
                    break;
            }

            $response = $request->send();

            if ($response->getStatusCode() == 200) {
                $task->setSuccess(true);
            } elseif ($response->getStatusCode() == 201) {
                $task->setStartDateTime(null);
                return $task;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $task->setSuccess(false);
        }

        $task->setCompleted();

        return $task;
    }
}
