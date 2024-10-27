SET @admin_email := 'admin@cerb.example';
SET @admin_name_first := 'Admin';
SET @admin_name_last := 'Worker';
SET @admin_tz := 'America/Los_Angeles';
SET @admin_pass_hash := '$2y$10$8lJozqfMeazUOASa4jybe.rB6DzwdwvbO2v2Ok8/YEFYIH9Xf19ga';

# INSERT INTO `mail_transport` (`id`, `name`, `extension_id`, `created_at`, `updated_at`, `params_json`) VALUES (1,'Null Mailer','core.mail.transport.null',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'[]');

INSERT INTO `address` (`id`, `email`, `contact_org_id`, `num_spam`, `num_nonspam`, `is_banned`, `is_defunct`, `updated`, `contact_id`, `host`, `mail_transport_id`, `worker_id`, `created_at`, `is_trusted`) VALUES (1,@admin_email,0,0,0,0,0,UNIX_TIMESTAMP(),0,substring(@admin_email,locate('@',@admin_email)+1),0,1,UNIX_TIMESTAMP(),0);
INSERT INTO `address` (`id`, `email`, `contact_org_id`, `num_spam`, `num_nonspam`, `is_banned`, `is_defunct`, `updated`, `contact_id`, `host`, `mail_transport_id`, `worker_id`, `created_at`, `is_trusted`) VALUES (2,'team@cerb.ai',0,0,0,0,0,UNIX_TIMESTAMP(),0,'cerb.ai',0,0,UNIX_TIMESTAMP(),0);

INSERT INTO `calendar` (`id`, `name`, `owner_context`, `owner_context_id`, `params_json`, `updated_at`, `timezone`) VALUES (1,'Office Hours','cerberusweb.contexts.app',0,'{\"manual_disabled\":\"0\",\"sync_enabled\":\"1\",\"start_on_mon\":\"1\",\"hide_start_time\":\"1\",\"color_available\":\"#A0D95B\",\"color_busy\":\"#C8C8C8\",\"series\":[]}',UNIX_TIMESTAMP(),'');
INSERT INTO `calendar` (`id`, `name`, `owner_context`, `owner_context_id`, `params_json`, `updated_at`, `timezone`) VALUES (2,CONCAT(@admin_name_first,'\'s Calendar'),'cerberusweb.contexts.worker',1,'{\"manual_disabled\":\"0\",\"sync_enabled\":\"1\",\"start_on_mon\":\"1\",\"hide_start_time\":\"1\",\"color_available\":\"#A0D95B\",\"color_busy\":\"#C8C8C8\",\"series\":[{\"datasource\":\"calendar.datasource.calendar\",\"sync_calendar_id\":\"1\"}]}',UNIX_TIMESTAMP(),'');

INSERT INTO `calendar_recurring_profile` (`id`, `event_name`, `is_available`, `calendar_id`, `tz`, `event_start`, `event_end`, `recur_start`, `recur_end`, `patterns`) VALUES (1,'Work (8a-6p)',1,1,@admin_tz,'08:00:00','17:59:59',0,0,'weekdays');

INSERT INTO `worker` (`id`, `first_name`, `last_name`, `title`, `is_superuser`, `is_disabled`, `at_mention_name`, `timezone`, `time_format`, `language`, `calendar_id`, `updated`, `email_id`, `gender`, `dob`, `location`, `phone`, `mobile`, `is_mfa_required`, `is_password_disabled`, `timeout_idle_secs`) VALUES (1,@admin_name_first,@admin_name_last,'Administrator',1,0,@admin_name_first,@admin_tz,'D, d M Y h:i a','en_US',2,UNIX_TIMESTAMP(),1,'',NULL,NULL,'','',0,0,600);

INSERT INTO `worker_auth_hash` (`worker_id`, `pass_hash`, `pass_salt`, `method`) VALUES (1,@admin_pass_hash,'',1);

INSERT INTO `workspace_page` (`id`, `name`, `owner_context`, `owner_context_id`, `extension_id`, `extension_params_json`, `updated_at`) VALUES (1,'Tutorial','cerb.contexts.workflow',1,'core.workspace.page.workspace',NULL,UNIX_TIMESTAMP());
INSERT INTO `workspace_page` (`id`, `name`, `owner_context`, `owner_context_id`, `extension_id`, `extension_params_json`, `updated_at`) VALUES (2,'Quickstart','cerberusweb.contexts.app',0,'core.workspace.page.workspace',NULL,UNIX_TIMESTAMP());
INSERT INTO `workspace_page` (`id`, `name`, `owner_context`, `owner_context_id`, `extension_id`, `extension_params_json`, `updated_at`) VALUES (3,'Home','cerberusweb.contexts.app',0,'core.workspace.page.workspace',NULL,UNIX_TIMESTAMP());

INSERT INTO `workspace_tab` (`id`, `name`, `workspace_page_id`, `pos`, `extension_id`, `params_json`, `updated_at`, `options_kata`) VALUES (1,'My Work',3,0,'core.workspace.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP(),NULL);
INSERT INTO `workspace_tab` (`id`, `name`, `workspace_page_id`, `pos`, `extension_id`, `params_json`, `updated_at`, `options_kata`) VALUES (2,'Open',3,1,'core.workspace.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP(),NULL);
INSERT INTO `workspace_tab` (`id`, `name`, `workspace_page_id`, `pos`, `extension_id`, `params_json`, `updated_at`, `options_kata`) VALUES (3,'Waiting',3,2,'core.workspace.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP(),NULL);

INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (1,'core.workspace.widget.worklist',1,'My Notifications',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.notification\",\"query_required\":\"isRead:no worker.id:{{current_worker_id}}\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"we_created_date\"]}',2,4,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (2,'core.workspace.widget.worklist',1,'My Tickets',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"owner.id:me status:o sort:-updated\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_updated_date\",\"t_group_id\",\"t_bucket_id\"]}',3,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (3,'core.workspace.widget.worklist',1,'My Tasks',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.task\",\"query_required\":\"status:o owner.id:me sort:due\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"t_due_date\",\"t_importance\",\"t_updated_date\"]}',4,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (4,'core.workspace.widget.worklist',1,'My Reminders',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.reminder\",\"query_required\":\"worker.id:me closed:n sort:remindAt\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"r_name\",\"r_remind_at\",\"r_updated_at\"]}',5,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (5,'core.workspace.widget.worklist',1,'My Drafts',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.mail.draft\",\"query_required\":\"worker.id:{{current_worker_id}}\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"m_hint_to\",\"m_type\",\"m_worker_id\",\"m_is_queued\",\"m_queue_delivery_date\",\"m_queue_fails\",\"m_updated\"]}',6,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (6,'core.workspace.widget.calendar',1,'My Calendar',UNIX_TIMESTAMP(),'{\"calendar_id\":\"{{current_worker_calendar_id}}\"}',7,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (7,'core.workspace.widget.worklist',2,'Open Tickets',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"status:o\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_group_id\",\"t_bucket_id\",\"t_owner_id\",\"t_updated_date\"]}',1,4,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (8,'core.workspace.widget.worklist',2,'Open Tasks',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.task\",\"query_required\":\"status:o\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_due_date\",\"t_importance\",\"t_updated_date\"]}',2,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (9,'core.workspace.widget.worklist',3,'Waiting Tasks',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.task\",\"query_required\":\"status:w\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_due_date\",\"t_owner_id\",\"t_importance\",\"t_reopen_at\"]}',1,2,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (10,'core.workspace.widget.worklist',3,'Tickets waiting for a client response',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"status:w reopen:0\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_group_id\",\"t_bucket_id\",\"t_owner_id\",\"t_updated_date\"]}',2,4,'content',NULL);
INSERT INTO `workspace_widget` (`id`, `extension_id`, `workspace_tab_id`, `label`, `updated_at`, `params_json`, `pos`, `width_units`, `zone`, `options_kata`) VALUES (11,'core.workspace.widget.worklist',3,'Tickets waiting for a specific time',UNIX_TIMESTAMP(),'{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"status:w reopen:\\\"-10 years to +20 years\\\"\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_group_id\",\"t_bucket_id\",\"t_owner_id\",\"t_reopen_at\"]}',3,4,'content',NULL);

INSERT INTO `workflow` (`id`, `name`, `description`, `created_at`, `updated_at`, `version`, `workflow_kata`, `config_kata`, `resources_kata`, `has_extensions`) VALUES (1,'cerb.tutorial','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),0,'','','records:\n  workspace_page/page_tutorial@int: 1\n',0);
INSERT INTO `workflow` (`id`, `name`, `description`, `created_at`, `updated_at`, `version`, `workflow_kata`, `config_kata`, `resources_kata`, `has_extensions`) VALUES (2,'cerb.quickstart','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),0,'','','records:\n  workspace_page/workspace_demo@int: 2\n',0);
INSERT INTO `workflow` (`id`, `name`, `description`, `created_at`, `updated_at`, `version`, `workflow_kata`, `config_kata`, `resources_kata`, `has_extensions`) VALUES (3,'cerb.demo.data','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),0,'','isDefaultGroup: yes','',0);

INSERT INTO `worker_pref` (`worker_id`, `setting`, `value`) VALUES (1,'dark_mode','1');
INSERT INTO `worker_pref` (`worker_id`, `setting`, `value`) VALUES (1,'menu_json','[1,2,3]');
INSERT INTO `worker_pref` (`worker_id`, `setting`, `value`) VALUES (1,'search_favorites_json','[\"cerberusweb.contexts.contact\",\"cerberusweb.contexts.address\",\"cerberusweb.contexts.org\",\"cerberusweb.contexts.task\",\"cerberusweb.contexts.ticket\"]');

INSERT INTO `devblocks_setting` (`plugin_id`, `setting`, `value`) VALUES ('cerberusweb.core','new_worker_default_page_ids','1,3');
REPLACE INTO `devblocks_setting` (`plugin_id`, `setting`, `value`) VALUES ('cerberusweb.core','mail_default_from_id','3');

INSERT INTO `contact_org` (`id`, `name`, `street`, `city`, `province`, `postal`, `country`, `phone`, `website`, `created`, `updated`, `email_id`) VALUES (1,'Webgroup Media, LLC.','440 N Barranca Ave #2048','Covina','California','91723','United States','+1-714-671-9090','https://cerb.ai',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),2);

INSERT INTO `worker_role` (`id`, `name`, `updated_at`, `privs_json`, `privs_mode`, `member_query_worker`, `editor_query_worker`, `reader_query_worker`) VALUES (1,'Everyone',UNIX_TIMESTAMP(),NULL,'all','isDisabled:n','isAdmin:y isDisabled:n','isDisabled:n');
INSERT INTO `worker_role` (`id`, `name`, `updated_at`, `privs_json`, `privs_mode`, `member_query_worker`, `editor_query_worker`, `reader_query_worker`) VALUES (2,'Admins',UNIX_TIMESTAMP(),NULL,'all','isAdmin:y isDisabled:n','isAdmin:y isDisabled:n','isDisabled:n');

INSERT INTO `worker_to_group` (`worker_id`, `group_id`, `is_manager`) VALUES (1,1,1);

