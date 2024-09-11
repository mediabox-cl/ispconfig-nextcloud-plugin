<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class nextcloud_plugin
{
    public string $plugin_name = 'nextcloud_plugin';
    public string $class_name = 'nextcloud_plugin';

    public string $plugin_dir;

    private array $nc_tables = [
        'mail_domain' => [
            'nc_enabled',
            'nc_quota',
            'nc_group'
        ],
        'mail_user' => [
            'nc_enabled',
            'nc_quota',
            'nc_group',
            'nc_adm_user',
            'nc_server',
            'nc_adm_server',
            'nc_domain',
            'nc_adm_domain'
        ]
    ];

    public function __construct()
    {
        $this->plugin_dir = ISPC_ROOT_PATH . '/lib/plugins/' . $this->plugin_name;
    }

    /**
     * This function is called when the plugin is loaded
     *
     * @return void
     */
    public function onLoad(): void
    {
        global $app;

        // Check if needed tables/columns exist
        $this->checkTables();

        // Register for the events

        // We need this in the Domain tab to restore missing vars needed by ISPConfig,
        // also we set the "active" var to an empty string in order to bypass the dkim
        // check part.
        $app->plugin->registerEvent('mail:mail_domain:on_before_insert', $this->plugin_name, 'mail_domain_edit');
        $app->plugin->registerEvent('mail:mail_domain:on_before_update', $this->plugin_name, 'mail_domain_edit');

        // Server - Insert tabs || fields (Remote)
        $app->plugin->registerEvent('admin:server_config:on_after_formdef', $this->plugin_name, 'server_config_form');
        $app->plugin->registerEvent('admin:server_config:on_remote_after_formdef', $this->plugin_name, 'server_config_form');

        // Domain - Insert tabs || fields (Remote)
        $app->plugin->registerEvent('mail:mail_domain:on_after_formdef', $this->plugin_name, 'mail_domain_form');
        $app->plugin->registerEvent('mail:mail_domain:on_remote_after_formdef', $this->plugin_name, 'mail_domain_form');

        // Mailbox - Insert tabs || fields (Remote)
        $app->plugin->registerEvent('mail:mail_user:on_after_formdef', $this->plugin_name, 'mail_user_form');
        $app->plugin->registerEvent('mail:mail_user:on_remote_after_formdef', $this->plugin_name, 'mail_user_form');
    }

    private function checkTables(): void
    {
        global $app;

        foreach ($this->nc_tables as $table => $columns) {
            // Check if table exist
            $list = "'" . implode("','", $columns) . "'";
            $sql = "SHOW COLUMNS FROM $table WHERE Field IN($list)";
            $result = $app->db->queryAllArray($sql);

            if (!empty($result)) {
                $diff = array_diff($columns, $result);
                if ($diff) {
                    $this->createColumns($table);
                }
            } else {
                $this->createColumns($table);
            }
        }
    }

    private function createColumns($table): void
    {
        global $app;

        $file = $this->plugin_dir . "/sql/$table.sql";

        if (is_file($file)) {
            $sql = preg_replace('/\s+/', ' ', file_get_contents($file));
            if ($sql) {
                $app->db->query($sql);
            }
        }
    }

    public function mail_domain_edit($event_name, $page_form): void
    {
        global $app;

        // INFO: This is a HACK because the mail domain part in ISPConfig
        // isn't prepared to use TABS :(

        // We need to set this because from our tab to the domain tab it
        // need the domain ID from $_GET and this is a POST.
        if (isset($page_form->dataRecord['id'])) {
            if (!isset($_GET['id'])) {
                $_GET['id'] = $page_form->dataRecord['id'];
            }
        }

        // Restore this vars in the form
        if (isset($page_form->dataRecord)) {
            $app->tpl->setVar(
                'server_value',
                ($page_form->dataRecord['server_id'] ?? ''),
                true
            );
            $app->tpl->setVar(
                'domain_value',
                ($page_form->dataRecord['domain'] ?? ''),
                true
            );
            $app->tpl->setVar(
                'policy_value',
                ($page_form->dataRecord['policy'] ?? ''),
                true
            );
            $app->tpl->setVar(
                'dkim_private_value',
                ($page_form->dataRecord['dkim_private'] ?? ''),
                true
            );
            $app->tpl->setVar(
                'dkim_public_value',
                ($page_form->dataRecord['dkim_public'] ?? ''),
                true
            );
            $app->tpl->setVar(
                'dkim_selector_value',
                ($page_form->dataRecord['dkim_selector'] ?? ''),
                true
            );
            $app->tpl->setVar(
                'dns_record_value',
                ($page_form->dataRecord['dns_record'] ?? ''),
                true
            );
        }
    }

    public function server_config_form($event_name, $page_form): void
    {
        $this->loadLang($page_form);

        $tabs = array(
            'nextcloud' => array(
                'title' => 'Nextcloud',
                'width' => 100,
                'template' => $this->plugin_dir . '/templates/server_config_edit.htm',
                'fields' => array(
                    'nc_enabled' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_account' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_url' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'filters' => array(
                            0 => array(
                                'event' => 'SAVE',
                                'type' => 'TRIM'
                            )
                        ),
                        'validators' => array(
                            0 => array(
                                'type' => 'CUSTOM',
                                'class' => 'validate_nextcloud',
                                'function' => 'check_url',
                                'errmsg' => 'nc_url_error_function'
                            )
                        ),
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_user' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'filters' => array(
                            0 => array(
                                'event' => 'SAVE',
                                'type' => 'TRIM'
                            )
                        ),
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_password' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_group' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'filters' => array(
                            0 => array(
                                'event' => 'SAVE',
                                'type' => 'TRIM'
                            )
                        ),
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_add' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'y',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_remove' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_delete' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                )
            )
        );

        $this->insert($tabs, $page_form);
    }

    public function mail_domain_form($event_name, $page_form): void
    {
        $this->loadLang($page_form);

        $tabs = array(
            'nextcloud' => array(
                'title' => 'Nextcloud',
                'width' => 100,
                'template' => $this->plugin_dir . '/templates/mail_domain_edit.htm',
                'fields' => array(
                    'nc_enabled' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_quota' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'validators' => array(
                            0 => array(
                                'type' => 'REGEX',
                                'regex' => '/^([0-9]+)$/',
                                'errmsg' => 'nc_quota_error_regex'
                            ),
                        ),
                        'default' => '0',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_group' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'filters' => array(
                            0 => array(
                                'event' => 'SAVE',
                                'type' => 'TRIM'
                            )
                        ),
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                )
            )
        );

        $this->insert($tabs, $page_form);
    }

    public function mail_user_form($event_name, $page_form): void
    {
        $this->loadLang($page_form);

        $tabs = array(
            'nextcloud' => array(
                'title' => 'Nextcloud',
                'width' => 100,
                'template' => $this->plugin_dir . '/templates/mail_user_edit.htm',
                'fields' => array(
                    'nc_enabled' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'y',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_quota' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'validators' => array(
                            0 => array(
                                'type' => 'REGEX',
                                'regex' => '/^([0-9]*)$/',
                                'errmsg' => 'nc_quota_user_error_regex'
                            ),
                        ),
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_group' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'TEXT',
                        'filters' => array(
                            0 => array(
                                'event' => 'SAVE',
                                'type' => 'TRIM'
                            )
                        ),
                        'default' => '',
                        'value' => '',
                        'maxlength' => '255'
                    ),
                    'nc_adm_user' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_server' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'y',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_adm_server' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_domain' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'y',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                    'nc_adm_domain' => array(
                        'datatype' => 'VARCHAR',
                        'formtype' => 'CHECKBOX',
                        'default' => 'n',
                        'value' => array(
                            1 => 'y',
                            0 => 'n'
                        )
                    ),
                )
            )
        );

        $this->insert($tabs, $page_form);
    }

    private function loadLang($page_form): void
    {
        global $app, $conf;

        $language = $app->functions->check_language(
            $_SESSION['s']['user']['language'] ?? $conf['language']
        );

        $file = $this->plugin_dir . "/lib/lang/$language.lng";

        if (!is_file($file)) {
            $file = $this->plugin_dir . "/lib/lang/en.lng";
        }

        @include $file;

        if (isset($page_form->wordbook) && isset($wb) && is_array($wb)) {

            if (is_array($page_form->wordbook)) {
                $page_form->wordbook = array_merge($page_form->wordbook, $wb);
            } else {
                $page_form->wordbook = $wb;
            }
        }
    }

    private function insert($tabs, $page_form): void
    {
        if (isset($page_form->formDef['tabs'])) {
            $page_form->formDef['tabs'] += $tabs;
        } elseif (isset($page_form->formDef['fields'])) {
            foreach ($tabs as $tab) {
                foreach ($tab['fields'] as $key => $value) {
                    $page_form->formDef['fields'][$key] = $value;
                }
            }
        }
    }
}