<?php

/**
 * Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 * Neither the name of ISPConfig nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class nextcloud_plugin
{
    /**
     * Max length for UID row in the DB
     */
    const MAX_UID_LENGTH = 64;

    /**
     * API Paths
     */
    const PATHS = [
        'user' => [
            'method' => 'GET',
            'path' => 'ocs/v1.php/cloud/users/{uid}',
            'header' => ['OCS-APIRequest: true'],
            'success' => 100,
        ],
        'delete' => [
            'method' => 'DELETE',
            'path' => 'ocs/v1.php/cloud/users/{uid}',
            'header' => ['OCS-APIRequest: true'],
            'success' => 100,
        ]
    ];

    // Plugin
    public string $plugin_name = 'nextcloud_plugin';
    public string $class_name = 'nextcloud_plugin';

    // Private variables
    public string $action = '';

    // Nextcloud
    private ?bool $nc_enabled = null;
    private string $nc_url;
    private string $nc_path;
    private string $nc_user;
    private string $nc_password;

    /**
     * This function is called during ISPConfig installation to determine
     * if a symlink shall be created for this plugin.
     */
    function onInstall(): bool
    {
        global $conf;

        return (bool) $conf['services']['mail'];
    }

    /**
     * This function is called when the plugin is loaded
     */
    public function onLoad(): void
    {
        global $app;

        // Register for the events

        // Mailboxes
        $app->plugins->registerEvent('mail_user_delete', $this->plugin_name, 'mail_user_delete');

        // Mail Domains
        $app->plugins->registerEvent('mail_domain_delete', $this->plugin_name, 'mail_domain_delete');
    }

    /**
     * Mail user delete event
     *
     * @param $event_name
     * @param $data
     *
     * @return void
     */
    public function mail_user_delete($event_name, $data): void
    {
        $this->loadConf();

        if ($this->nc_enabled) {
            $this->user_delete($data['old']['email']);
        }
    }

    /**
     * Mail domain delete event
     *
     * @param $event_name
     * @param $data
     *
     * @return void
     */
    public function mail_domain_delete($event_name, $data): void
    {
        global $app;

        $this->loadConf();

        if ($this->nc_enabled) {
            $mail_users = $app->db->queryAllRecords("SELECT email FROM mail_user WHERE email like ?", '%@' . $data['old']['domain']);

            if (is_array($mail_users)) {
                foreach ($mail_users as $user) {
                    $this->user_delete($user['email']);
                }
            }
        }
    }

    /**
     * Delete user
     *
     * @param string $email
     *
     * @return void
     */
    private function user_delete(string $email): void
    {
        global $app;

        $uid = $this->getUid($email);

        $this->nc_path = str_replace('{uid}', $uid, self::PATHS['delete']['path']);
        $data = $this->call(self::PATHS['delete']['method'], self::PATHS['delete']['header']);

        if (!$data['error'] && $data['response']) {
            $status = $this->getStatus($data['response']);

            if ($status === self::PATHS['delete']['success']) {
                $app->log("Deleted the Nextcloud account for User: $uid", LOGLEVEL_WARN);
            } else {
                $app->log("Can't deleted the Nextcloud account for User: $uid - Status code: $status", LOGLEVEL_ERROR);
            }
        } else {
            $app->log("Can't deleted the Nextcloud account for User: $uid - Error: " . $data['description'], LOGLEVEL_ERROR);
        }
    }

    /**
     * Get status code from  xml response
     *
     * @param string $xml
     *
     * @return int
     */
    private function getStatus(string $xml): int
    {
        if (preg_match('/<statuscode>(\d+)<\/statuscode>/', $xml, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Compute UID from email
     *
     * @param string $email
     *
     * @return string
     */
    private function getUid(string $email): string
    {
        $parts = explode('@', mb_strtolower(trim($email)));
        $mailDomain = array_pop($parts);
        $mailUser = implode('@', $parts);

        $uid = "$mailUser.$mailDomain";

        if (mb_strlen($uid) > self::MAX_UID_LENGTH) {
            $uid = hash('sha256', $uid);
        }

        return $uid;
    }

    /**
     * Curl call
     *
     * @param string $method
     * @param array $headers
     *
     * @return false|array
     */
    private function call(string $method, array $headers): false|array
    {
        $url = rtrim($this->nc_url, '/') . '/' . $this->nc_path;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->nc_user . ':' . $this->nc_password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $response = curl_exec($ch);
        $error = curl_errno($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'http' => $http,
            'error' => $error,
            'description' => ($error ? curl_strerror($error) : ''),
            'response' => $response
        ];
    }

    /**
     * Load config
     *
     * @return void
     */
    private function loadConf(): void
    {
        global $app, $conf;

        if ($this->nc_enabled === null) {
            // load the server specific configuration options for nextcloud
            $app->uses('getconf');
            $nc_config = $app->getconf->get_server_config($conf['server_id'], 'nextcloud');

            if (
                is_array($nc_config) &&
                isset($nc_config['nc_account']) &&
                $nc_config['nc_account'] == 'y' &&
                isset($nc_config['nc_url']) &&
                $nc_config['nc_url'] &&
                isset($nc_config['nc_user']) &&
                $nc_config['nc_user'] &&
                isset($nc_config['nc_password']) &&
                $nc_config['nc_password']
            ) {
                $this->nc_url = $nc_config['nc_url'];
                $this->nc_user = $nc_config['nc_user'];
                $this->nc_password = $nc_config['nc_password'];
                $this->nc_enabled = true;
            } else {
                $this->nc_enabled = false;
            }
        }
    }
}