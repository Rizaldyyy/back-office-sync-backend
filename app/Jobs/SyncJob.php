<?php

namespace App\Jobs;

use App\Traits\GuzzleApi;

class SyncJob extends Job
{
    use GuzzleApi;

    public $timeout = 60;
    public $failOnTimeout = true;

    private $method;
    private $url;
    private $params;

    public function __construct(string $method, string $url, mixed $params)
    {
        $this->method = $method;
        $this->url = $url;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = $this->sendRequest($this->method, $this->url, $this->params);

        \Log::channel('sync')->info("Status: " . $result->success . " | Message: " . $result->message);
    }
}
