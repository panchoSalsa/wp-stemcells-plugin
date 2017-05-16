<?php
namespace Allaerd\Import;

class Scheduler extends AsyncRequest
{
    protected $finished = false;

    protected $identifier = 'aem_async_request';

    public $maxExecutionTime = 30;

    public function __construct()
    {
        add_action('wp_ajax_aem_async_request', array ($this, 'maybe_handle'));
        add_action('wp_ajax_nopriv_aem_async_request', array ($this, 'maybe_handle'));
        add_action('aem_async_request', array ($this, 'maybe_handle'));
    }

    public function setMaxExecutionTime()
    {
        $max = ini_get('max_execution_time');
        if ($max) {
            $this->maxExecutionTime = (int)($max * 0.8);
        } else {
            $this->maxExecutionTime = 25;
        }
        error_log($this->maxExecutionTime);
    }

    public function schedule()
    {
        $this->next();
        $this->dispatch();
    }

    public function next()
    {
        wp_schedule_single_event(time() - 5, 'aem_async_request');
    }

    public function handle()
    {
        $this->setStartTime();
        if ($this->are_we_running()) {
            return;
        }

        $this->lockProcess();

        while ($this->canWeProceed() && $this->finished == false) {
            sleep(1);
            $this->task();
        }

        $this->unlockProcess();

        if ($this->finished == false) {
            $this->schedule();

            return;
        }
    }


    public function canWeProceed()
    {
        error_log($this->maxExecutionTime);

        return (($this->startTime + 20 - time()) > 0) ? true : false;
    }

    public function task()
    {
        $count = get_site_option('testing_count');
        if ($count == false) {
            $count = 0;
        }

        $count++;

        update_site_option('testing_count', $count);

        error_log($count);

        if ($count >= 100) {
            $this->finished = true;
            delete_site_option('testing_count');
        }
    }

    /**
     * @return int
     */
    public function elapsedTime()
    {
        return time() - $this->startTime;
    }
}