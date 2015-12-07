<?php
namespace Disque\Console;

use Disque\Queue\JobInterface;

interface HandlerInterface
{
    /**
     * Handle a job
     *
     * @param JobInterface $job Job
     * @return void
     */
    public function handle(JobInterface $job);
}