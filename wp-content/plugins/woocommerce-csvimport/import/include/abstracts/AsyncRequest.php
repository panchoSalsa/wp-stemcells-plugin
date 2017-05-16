<?php

namespace Allaerd\Import;


abstract class AsyncRequest
{

    protected $startTime;

    protected $data = array ('action' => 'aem_async_request');

    public function dispatch()
    {
        $url = add_query_arg($this->get_query_args(), $this->get_query_url());
        $args = $this->get_post_args();
        wp_remote_post(esc_url_raw($url), $args);
    }

    public function setStartTime()
    {
        $this->startTime = time();
    }

    public function unlockProcess()
    {
        delete_site_option('aem_async_request');
    }

    public function lockProcess()
    {
        update_site_option('aem_async_request', 'running');
    }

    public function are_we_running()
    {
        if (get_site_option('aem_async_request')) {
            return true;
        } else {
            return false;
        }
    }

    protected function get_query_args()
    {
        return array (
            'action' => 'aem_async_request',
            'nonce'  => wp_create_nonce('aem_async_request'),
        );
    }

    protected function get_query_url()
    {
        return admin_url('admin-ajax.php');
    }

    protected function get_post_args()
    {
        return array (
            'action'    => 'aem_async_request',
            'timeout'   => 0.01,
            'blocking'  => false,
            'body'      => $this->data,
            'cookies'   => $_COOKIE,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
        );
    }

    public function maybe_handle()
    {
        $this->handle();
        wp_die();
    }

    abstract protected function handle();

}
