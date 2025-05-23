<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.27
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Export_Import_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
define('WPVIVID_IMPORT_EXPORT_DIR_ADDON', 'ImportandExport');

class WPvivid_Export_Import_addon
{
    private $replacing_table;
    private $search_string;
    private $replace_string;
    private $new_prefix;
    private $db_method;

    public $main_tab;
    public $sub_tab;

    public $end_shutdown_function;

    public function __construct()
    {
        add_filter('wpvivid_get_toolbar_menus',array($this,'get_toolbar_menus'),22);

        //actions
        add_action('admin_head', array($this, 'my_admin_custom_styles'));
        add_action('wpvivid_handle_export_success',array($this,'handle_export_success'),10);
        add_action('wpvivid_handle_export_failed',array($this,'handle_export_failed'),10, 2);

        //ajax
        add_action('wp_ajax_wpvivid_export_post_step2', array($this, 'export_post_step2'));
        add_action('wp_ajax_wpvivid_export_now', array($this, 'export_now'));
        add_action('wp_ajax_wpvivid_prepare_export_post', array($this, 'prepare_export_post'));
        add_action('wp_ajax_wpvivid_export_list_tasks', array($this, 'list_tasks'));
        add_action('wp_ajax_wpvivid_get_post_list', array($this, 'get_list'));
        add_action('wp_ajax_wpvivid_get_post_list_page', array($this, 'get_list_page'));
        add_action('wp_ajax_wpvivid_get_import_list_page', array($this, 'get_import_list_page'));
        add_action('wp_ajax_wpvivid_delete_export_list',array($this,'delete_export_list'),10);
        add_action('wp_ajax_wpvivid_start_import', array($this, 'start_import'));
        add_action('wp_ajax_wpvivid_download_export_backup', array($this, 'wpvivid_download_export_backup'));
        add_action('wp_ajax_wpvivid_check_import_file', array($this, 'check_import_file'));
        add_action('wp_ajax_wpvivid_upload_import_files',array($this,'upload_import_files'));
        add_action('wp_ajax_wpvivid_upload_import_file_complete', array($this, 'upload_import_file_complete'));
        add_action('wp_ajax_wpvivid_get_import_progress', array($this, 'get_import_progress'));
        add_action('wp_ajax_wpvivid_scan_import_folder', array($this, 'wpvivid_scan_import_folder'));
        add_action('wp_ajax_wpvivid_calc_import_folder_size', array($this, 'calc_import_folder_size'));
        add_action('wp_ajax_wpvivid_clean_import_folder', array($this, 'clean_import_folder'));
        add_action('wp_ajax_wpvivid_search_and_replace', array($this, 'search_and_replace'));
        add_action('wp_ajax_wpvivid_get_replace_progress', array($this, 'get_replace_progress'));

        //dashboard
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));

        $this->end_shutdown_function = false;
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-use-export';
        $cap['display']='Import/Export posts and pages';
        $cap['menu_slug']=strtolower(sprintf('%s-export-import', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['index']=17;
        $cap['icon']='<span class="dashicons dashicons-external wpvivid-dashicons-grey"></span>';
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-export-import';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-export-import';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_export_import');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Export/Import Page');
            $submenu['menu_title'] = 'Export/Import Page';
            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-export-import");
            $submenu['menu_slug'] = strtolower(sprintf('%s-export-import', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 14;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        if(isset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_export_import']) && !empty($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_export_import'])){
            unset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_export_import']);
        }
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_export_import');
        if($display)
        {
            $menu['id'] = 'wpvivid_admin_menu_export_import';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Export/Import Page';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-export-import');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-export-import');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-export-import");
            $menu['index'] = 14;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }

        if(isset($toolbar_menus['wpvivid_admin_menu'])&&isset($toolbar_menus['wpvivid_admin_menu']['child']))
        {
            if(isset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_export_import']))
            {
                $menu=$toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_export_import'];
                $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-export-import");
                $toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_export_import']=$menu;
            }
        }

        return $toolbar_menus;
    }

    /***** export import actions begin *****/
    public function my_admin_custom_styles(){
        $output_css = '<style type="text/css">    
                        .column-file_name { width:25% }
                        .column-export_type { width:8% }
                        .column-posts_count { width:8% }
                        .column-media_size { width:8% }
                        .column-import { width:8% }
                        </style>';
        echo $output_css;
    }

    public function handle_export_success($task_id){
        global $wpvivid_plugin;
        WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id,false,'completed');

        $wpvivid_plugin->wpvivid_log->WriteLog('Finished to export post','notice');
    }

    public function handle_export_failed($task_id){
    }
    /***** export import actions end *****/

    /***** export import useful function begin *****/
    public function deal_shutdown_error($task_id){
        if($this->end_shutdown_function===false)
        {
            global $wpvivid_plugin;

            $last_error = error_get_last();
            if (!empty($last_error) && !in_array($last_error['type'], array(E_NOTICE,E_WARNING,E_USER_NOTICE,E_USER_WARNING,E_DEPRECATED), true))
            {
                $error = $last_error;
            } else {
                $error = false;
            }
            if (WPvivid_Exporter_taskmanager_Ex::get_task($task_id) !== false)
            {
                if ($wpvivid_plugin->wpvivid_log->log_file_handle == false)
                {
                    $wpvivid_plugin->wpvivid_log->OpenLogFile(WPvivid_Exporter_taskmanager_Ex::get_task_options($task_id, 'log_file_name'));
                }

                $status = WPvivid_Exporter_taskmanager_Ex::get_backup_task_status($task_id);

                $message='in shutdown';

                if ($error !== false)
                {
                    $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];
                }
                WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id, false, 'error', false, $status['resume_count'], $message);
                if ($wpvivid_plugin->wpvivid_log)
                    $wpvivid_plugin->wpvivid_log->WriteLog($message, 'error');
            }
            die();
        }
    }

    public function deal_import_shutdown_error(){
        if($this->end_shutdown_function===false){
            $last_error = error_get_last();
            if (!empty($last_error) && !in_array($last_error['type'], array(E_NOTICE,E_WARNING,E_USER_NOTICE,E_USER_WARNING,E_DEPRECATED), true)) {
                $error = $last_error;
            } else {
                $error = false;
            }
            $ret['result'] = 'failed';
            if ($error === false) {
                $ret['error'] = 'unknown Error';
            } else {
                $ret['error'] = 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];
                error_log($ret['error']);
            }
            $id = uniqid('wpvivid-');
            $log_file_name = $id . '_import';
            $log = new WPvivid_Log();
            $log->CreateLogFile($log_file_name, 'no_folder', 'import');
            $log->WriteLog($ret['error'], 'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            echo json_encode($ret);
            die();
        }
    }

    public function export_date_options($post_type = 'post'){
        global $wpdb, $wp_locale;

        $months = $wpdb->get_results(
            $wpdb->prepare(
                "
		SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
		FROM $wpdb->posts
		WHERE post_type = %s AND post_status != 'auto-draft'
		ORDER BY post_date DESC
	",
                $post_type
            )
        );

        $month_count = count( $months );
        if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
            return;
        }

        foreach ( $months as $date ) {
            if ( 0 == $date->year ) {
                continue;
            }

            $month = zeroise( $date->month, 2 );
            echo '<option value="' . $date->year . '-' . $month . '">' . $wp_locale->get_month( $month ) . ' ' . $date->year . '</option>';
        }
    }

    public function export_post($task_id){
        $this->end_shutdown_function = false;
        register_shutdown_function(array($this,'deal_shutdown_error'),$task_id);
        @ignore_user_abort(true);

        WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id,true,'running');

        global $wpvivid_plugin;

        $wpvivid_plugin->wpvivid_log->OpenLogFile(WPvivid_Exporter_taskmanager_Ex::get_task_options($task_id,'log_file_name'));
        $wpvivid_plugin->wpvivid_log->WriteLog('Start export posts.','notice');
        $wpvivid_plugin->wpvivid_log->WriteLogHander();
        $this->flush($task_id);

        $export=new WPvivid_Exporter_Ex();

        @set_time_limit(900);
        try
        {
            $ret = $export->export($task_id);
            if($ret['result']=='success')
            {
                do_action('wpvivid_handle_export_success', $task_id, true);
            }
            else
            {
                $wpvivid_plugin->wpvivid_log->WriteLog($ret['error'],'error');
                WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id, false, 'error', false, false, $ret['error']);
                do_action('wpvivid_handle_export_failed', $task_id, true);
            }

        }
        catch (Exception $error)
        {
            $message = 'An error has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            error_log($message);
            WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id,false,'error',false,false,$message);
            $wpvivid_plugin->wpvivid_log->WriteLog($message,'error');
            $this->end_shutdown_function=true;
            die();
        }

        echo json_encode($ret);
        $this->end_shutdown_function=true;
        die();
    }

    public function wpvivid_check_import_file_name($file_name){
        if(preg_match('/wpvivid-.*_.*_export_.*\.zip$/', $file_name))
        {
            if(preg_match('/wpvivid-(.*?)_/',$file_name,$matches))
            {
                $id= $matches[0];
                $id=substr($id,0,strlen($id)-1);
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['id']=$id;
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']=$file_name.' is not the file exported by WPvivid backup plugin.';
            }
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$file_name.' is not the file exported by WPvivid backup plugin.';
        }
        return $ret;
    }

    public function check_is_import_file($file_name){
        $ret=$this->get_backup_file_info($file_name);
        if($ret['result'] === WPVIVID_PRO_SUCCESS)
        {
            $export_type_support_array = array('post', 'page');
            if(isset($ret['json_data']['post_type']) && in_array($ret['json_data']['post_type'], $export_type_support_array))
            {
                $ret['export_type']=$ret['json_data']['post_type'];
                $ret['export_comment']=isset($ret['json_data']['post_comment']) ? $ret['json_data']['post_comment'] : 'N/A';
                $ret['export_time']=isset($ret['json_data']['create_time']) ? $ret['json_data']['create_time'] : '';
                $ret['posts_count']=isset($ret['json_data']['posts_count']) ? $ret['json_data']['posts_count'] : 0;
                $ret['media_size']=isset($ret['json_data']['media_size']) ? $ret['json_data']['media_size'] : 0;
                $ret['time']=isset($ret['json_data']['create_time']) ? $ret['json_data']['create_time'] : time();
                return $ret;
            }
            else{
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error'] = 'The backup is not an import file.';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function get_backup_file_info($file_name){
        if(!class_exists('WPvivid_ZipClass'))
            include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
        $zip=new WPvivid_ZipClass();
        $ret=$zip->get_json_data($file_name, 'export');
        if($ret['result'] === WPVIVID_PRO_SUCCESS)
        {
            $json=$ret['json_data'];
            $json = json_decode($json, 1);
            if (is_null($json))
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Failed to decode json');
            } else {
                return array('result'=>WPVIVID_PRO_SUCCESS,'json_data'=>$json);
            }
        }
        else {
            return $ret;
        }
    }

    public function task_monitor($task_id){
        global $wpvivid_plugin;

        if(WPvivid_Exporter_taskmanager_Ex::get_task($task_id)!==false)
        {
            if($wpvivid_plugin->wpvivid_log->log_file_handle==false)
            {
                $wpvivid_plugin->wpvivid_log->OpenLogFile(WPvivid_Exporter_taskmanager_Ex::get_task_options($task_id,'log_file_name'));
            }

            $status=WPvivid_Exporter_taskmanager_Ex::get_backup_task_status($task_id);

            if($status['str']=='running'||$status['str']=='error'||$status['str']=='no_responds')
            {
                $limit=900;
                $time_spend=time()-$status['timeout'];

                if($time_spend>=$limit)
                {
                    //time out
                    $message=__('Task time out.', 'wpvivid');
                    WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id,false,'error',false,$status['resume_count'],$message);
                    if($wpvivid_plugin->wpvivid_log)
                        $wpvivid_plugin->wpvivid_log->WriteLog($message,'error');
                    $wpvivid_plugin->wpvivid_log->CloseFile();
                    WPvivid_error_log::create_error_log($wpvivid_plugin->wpvivid_log->log_file);
                }
                else {
                    $time_spend=time()-$status['run_time'];
                    if($time_spend>180)
                    {
                        $wpvivid_plugin->wpvivid_log->WriteLog('Not responding for a long time.','notice');
                        WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id,false,'no_responds',false,$status['resume_count']);
                    }
                }
            }
        }
    }

    public function clean_tmp_files($path, $filename){
        $handler=opendir($path);
        if($handler!==false)
        {
            while(($file=readdir($handler))!==false) {
                if (!is_dir($path.$file) && preg_match('/wpvivid-.*_.*_.*\.tmp$/', $file)) {
                    $iPos = strrpos($file, '_');
                    $file_temp = substr($file, 0, $iPos);
                    if($file_temp === $filename) {
                        @unlink($path.$file);
                    }
                }
            }
            @closedir($handler);
        }
    }

    public function upload_import_dir($uploads){
        $uploads['path'] = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.WPvivid_Setting::get_backupdir().DIRECTORY_SEPARATOR.WPVIVID_IMPORT_EXPORT_DIR_ADDON;
        return $uploads;
    }

    public function get_file_id($file_name){
        if(preg_match('/wpvivid-.*_.*_to_.*\.zip$/',$file_name))
        {
            if(preg_match('/wpvivid-(.*?)_/',$file_name,$matches))
            {
                $id= $matches[0];
                $id=substr($id,0,strlen($id)-1);
                return $id;
            }
            else
            {
                $id=uniqid('wpvivid-');
                return $id;
            }
        }
        else
        {
            $id=uniqid('wpvivid-');
            return $id;
        }
    }

    private function flush($task_id){
        $ret['result']='success';
        $ret['task_id']=$task_id;
        $json=json_encode($ret);
        if(!headers_sent())
        {
            header('Content-Length: '.strlen($json));
            header('Connection: close');
            header('Content-Encoding: none');
        }


        if (session_id())
            session_write_close();
        echo $json;

        if(function_exists('fastcgi_finish_request'))
        {
            fastcgi_finish_request();
        }
        else
        {
            ob_flush();
            flush();
        }
    }

    public static function get_database_tables()
    {
        global $wpdb;
        if (is_multisite() && !defined('MULTISITE')) {
            $prefix = $wpdb->base_prefix;
        } else {
            $prefix = $wpdb->get_blog_prefix(0);
        }
        $ret['result'] = 'success';
        $ret['html'] = '';
        if (empty($prefix)) {
            echo json_encode($ret);
            die();
        }

        $default_table = array($prefix . 'commentmeta', $prefix . 'comments', $prefix . 'links', $prefix . 'options', $prefix . 'postmeta', $prefix . 'posts', $prefix . 'term_relationships',
            $prefix . 'term_taxonomy', $prefix . 'termmeta', $prefix . 'terms', $prefix . 'usermeta', $prefix . 'users');

        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            echo json_encode($ret);
            die();
        }

        $tables_info = array();
        $base_table = '';
        $other_table = '';
        $has_base_table = false;
        $has_other_table = false;

        $database_html = '';

        foreach ($tables as $row) {
            if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                continue;
            }

            $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
            $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);


            if (in_array($row["Name"], $default_table)) {
                $has_base_table = true;

                $base_table .= '<div class="wpvivid-text-line">
                                        <input type="checkbox" option="tool_base_db" name="tool_Database" value="'.esc_html($row["Name"]).'" style="margin: 0;" />
                                        <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                    </div>';
            } else {
                $has_other_table = true;

                $other_table .= '<div class="wpvivid-text-line">
                                        <input type="checkbox" option="tool_other_db" name="tool_Database" value="'.esc_html($row["Name"]).'" style="margin: 0;" />
                                        <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                     </div>';
            }
        }

        $base_table_html = '';
        $other_table_html = '';
        if ($has_base_table) {
            $base_table_html .= '<div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;padding-left:0.5em;">
                                    <div>
                                        <p>
                                            <span class="dashicons dashicons-list-view wpvivid-dashicons-blue"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="wpvivid-tool-base-table-check" style="margin: 0;"></span>
												<span><strong>Wordpress default tables</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow:auto;">
                                        '.$base_table.'
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>';
        }

        if ($has_other_table) {
            $other_table_width = '70%';

            $other_table_html .= '<div style="width:'.$other_table_width.'; float:left;box-sizing:border-box;padding-left:0.5em;">
                                    <div>
                                        <p>
                                            <span class="dashicons dashicons-list-view wpvivid-dashicons-green"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="wpvivid-tool-other-table-check" style="margin: 0;"></span>
												<span><strong>Tables created by plugins or themes</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;">
                                        '.$other_table.'
                                    </div>
                                 </div>';
        }

        $database_html .= $base_table_html . $other_table_html;

        return $database_html;
    }

    public function search_and_replace_table($table_name)
    {
        global $wpdb,$wpvivid_plugin;

        $this->replacing_table=$table_name;

        $skip_table=false;
        if(apply_filters('wpvivid_restore_db_skip_replace_tables',$skip_table,$table_name))
        {
            return ;
        }

        $wpvivid_plugin->restore_data->write_log('Start replacing row(s) from '.$table_name.'.', 'notice');

        $query = 'SELECT COUNT(*) FROM `'.$table_name.'`';

        $result=$this->db_method->query($query,ARRAY_N);
        if($result && sizeof($result)>0)
        {
            $count=$result[0][0];
            $wpvivid_plugin->restore_data->write_log('Count of rows in '.$table_name.': '.$count, 'notice');

            if($count==0)
            {
                return ;
            }

            $query='DESCRIBE `'.$table_name.'`';
            $result=$this->db_method->query($query,ARRAY_A);
            $columns=array();
            foreach ($result as $data)
            {
                $column['Field']=$data['Field'];
                if($data['Key']=='PRI')
                    $column['PRI']=1;
                else
                    $column['PRI']=0;

                if($data['Type']=='mediumblob')
                {
                    $column['skip']=1;
                }
                $columns[]=$column;
            }
            $page=5000;

            $update_query='';

            $start_row=0;
            for ($current_row = $start_row; $current_row <= $count; $current_row += $page)
            {
                $wpvivid_plugin->restore_data->write_log('Replace the row in '.$current_row. ' line.', 'notice');
                $query = 'SELECT * FROM `'.$table_name.'` LIMIT '.$current_row.', '.$page;

                $result=$this->db_method->query($query,ARRAY_A);
                if($result && sizeof($result)>0)
                {
                    $rows = $result;
                    foreach ($rows as $row)
                    {
                        $update=array();
                        $where=array();
                        foreach ($columns as $column)
                        {
                            if(isset($column['skip']))
                            {
                                continue;
                            }

                            $old_data = $row[$column['Field']];
                            if($column['PRI']==1)
                            {
                                $wpdb->escape_by_ref($old_data);
                                $temp_where='`'.$column['Field'].'` = \'' . $old_data . '\'';
                                if (is_callable(array($wpdb, 'remove_placeholder_escape')))
                                    $temp_where = $wpdb->remove_placeholder_escape($temp_where);
                                $where[] = $temp_where;
                            }

                            $skip_row=false;
                            if(apply_filters('wpvivid_restore_db_skip_replace_rows',$skip_row,$table_name,$column['Field']))
                            {
                                continue;
                            }
                            $new_data=$this->replace_row_data($old_data);
                            if($new_data==$old_data)
                                continue;

                            $wpdb->escape_by_ref($new_data);
                            if (is_callable(array($wpdb, 'remove_placeholder_escape')))
                                $new_data = $wpdb->remove_placeholder_escape($new_data);
                            $update[] = '`'.$column['Field'].'` = \'' . $new_data . '\'';
                        }

                        if(!empty($update)&&!empty($where))
                        {
                            $temp_query = 'UPDATE `'.$table_name.'` SET '.implode(', ', $update).' WHERE '.implode(' AND ', array_filter($where)).';';
                            $type=$this->db_method->get_type();

                            if($type=='pdo_mysql')
                            {
                                if($update_query=='')
                                {
                                    $update_query=$temp_query;
                                    if(strlen($update_query)>$this->db_method->get_max_allow_packet())
                                    {
                                        $wpvivid_plugin->restore_data->write_log('update replace rows', 'notice');
                                        $this->execute_sql($update_query);

                                        $update_query='';
                                    }
                                }
                                else if(strlen($temp_query)+strlen($update_query)>$this->db_method->get_max_allow_packet())
                                {
                                    $wpvivid_plugin->restore_data->write_log('update replace rows', 'notice');
                                    $this->execute_sql($update_query);

                                    $update_query='';
                                }
                                else
                                {
                                    $update_query.=$temp_query;
                                }
                            }
                            else
                            {
                                $update_query=$temp_query;
                                $this->execute_sql($update_query);
                                $update_query='';
                            }

                        }
                        //return;
                    }
                }
            }

            if(!empty($update_query))
            {
                $wpvivid_plugin->restore_data->write_log('update replace rows', 'notice');
                $this->execute_sql($update_query);
            }
        }
        $wpvivid_plugin->restore_data->write_log('finish replace rows', 'notice');
    }

    private function replace_row_data($old_data)
    {
        $unserialize_data = @unserialize($old_data);
        if($unserialize_data===false)
        {
            $old_data=$this->replace_string_v2($old_data);
        }
        else
        {
            $old_data=$this->replace_serialize_data($unserialize_data);
            $old_data=serialize($old_data);
        }

        return $old_data;
    }

    private function execute_sql($query)
    {
        return $this->db_method->execute_sql($query);
    }

    public function replace_string_v2($old_string)
    {
        if(!is_string($old_string))
        {
            return $old_string;
        }

        $from=array();
        $to=array();

        $new_url_use_https=false;
        if (0 === stripos($this->replace_string, 'https://')|| stripos($this->replace_string, 'https:\/\/'))
        {
            $new_url_use_https=true;
        }
        else if (0 === stripos($this->search_string, 'http://')|| stripos($this->search_string, 'http:\/\/'))
        {
            $new_url_use_https=false;
        }

        if(substr($this->replacing_table, strlen($this->new_prefix))=='posts'||substr($this->replacing_table, strlen($this->new_prefix))=='postmeta')
        {
            $remove_http_link=$this->get_remove_http_link($this->search_string);
            if($remove_http_link!==false)
            {
                $new_remove_http_link=$this->get_remove_http_link($this->replace_string);
                $from[]=$remove_http_link;
                $to[]=$new_remove_http_link;

                if($new_url_use_https)
                {
                    $from[]='http:'.$new_remove_http_link;
                    $to[]='https:'.$new_remove_http_link;
                }
                else
                {
                    $from[]='https:'.$new_remove_http_link;
                    $to[]='http:'.$new_remove_http_link;
                }

                $quote_old_site_url=$this->get_http_link_at_quote($remove_http_link);
                $quote_new_site_url=$this->get_http_link_at_quote($new_remove_http_link);
                $from[]=$quote_old_site_url;
                $to[]=$quote_new_site_url;

                if($new_url_use_https)
                {
                    $from[]='http:'.$quote_new_site_url;
                    $to[]='https:'.$quote_new_site_url;
                }
                else
                {
                    $from[]='https:'.$quote_new_site_url;
                    $to[]='http:'.$quote_new_site_url;
                }
            }
            else
            {
                $remove_http_link=$this->get_remove_http_link_ex($this->search_string);
                if($remove_http_link!==false)
                {
                    $new_remove_http_link=$this->get_remove_http_link_ex($this->replace_string);
                    $from[]=$remove_http_link;
                    $to[]=$new_remove_http_link;

                    if($new_url_use_https)
                    {
                        $from[]='http:'.$new_remove_http_link;
                        $to[]='https:'.$new_remove_http_link;
                    }
                    else
                    {
                        $from[]='https:'.$new_remove_http_link;
                        $to[]='http:'.$new_remove_http_link;
                    }
                }
                else
                {
                    $from[]=$this->search_string;
                    $to[]=$this->replace_string;
                }
            }
        }
        else
        {
            $from[]=$this->search_string;
            $to[]=$this->replace_string;
        }

        if(!empty($from)&&!empty($to))
        {
            $old_string=str_replace($from,$to,$old_string);
        }

        return $old_string;
    }

    private function replace_serialize_data($data)
    {
        if(is_string($data))
        {
            $serialize_data =@unserialize($data);
            if($serialize_data===false)
            {
                $data=$this->replace_string_v2($data);
            }
            else
            {
                $data=serialize($this->replace_serialize_data($serialize_data));
            }
        }
        else if(is_array($data))
        {
            foreach ($data as $key => $value)
            {
                if(is_string($value))
                {
                    $data[$key]=$this->replace_string_v2($value);
                }
                else if(is_array($value))
                {
                    $data[$key]=$this->replace_serialize_data($value);
                }
                else if(is_object($value))
                {
                    if (is_a($value, '__PHP_Incomplete_Class'))
                    {
                        //
                    }
                    else
                    {
                        $data[$key]=$this->replace_serialize_data($value);
                    }
                }
            }
        }
        else if(is_object($data))
        {
            $temp = $data; // new $data_class();
            if (is_a($data, '__PHP_Incomplete_Class'))
            {

            }
            else
            {
                $props = get_object_vars($data);
                foreach ($props as $key => $value)
                {
                    if (strpos($key, "\0")===0)
                        continue;
                    if(is_string($value))
                    {
                        $temp->$key =$this->replace_string_v2($value);
                    }
                    else if(is_array($value))
                    {
                        $temp->$key=$this->replace_serialize_data($value);
                    }
                    else if(is_object($value))
                    {
                        $temp->$key=$this->replace_serialize_data($value);
                    }
                }
            }
            $data = $temp;
            unset($temp);
        }

        return $data;
    }

    private function get_remove_http_link($url)
    {
        if (0 === stripos($url, 'https://'))
        {
            $mix_link = '//'.substr($url, 8);
        } elseif (0 === stripos($url, 'http://')) {
            $mix_link = '//'.substr($url, 7);
        }
        else
        {
            $mix_link=false;
        }
        return $mix_link;
    }

    private function get_remove_http_link_ex($url)
    {
        if (0 === stripos($url, 'https://'))
        {
            $mix_link = '\/\/'.substr($url, 8);
        } elseif (0 === stripos($url, 'http://')) {
            $mix_link = '\/\/'.substr($url, 7);
        }
        else
        {
            $mix_link=false;
        }
        return $mix_link;
    }

    private function get_http_link_at_quote($url)
    {
        return str_replace('/','\/',$url);
    }
    /***** export import useful function end *****/

    /***** export import ajax begin *****/
    public function export_post_step2(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (isset($_POST['post_type'])) {
                global $wpdb;
                $post_type = sanitize_text_field($_POST['post_type']);
                $descript_type = $post_type === 'post' ? 'posts' : 'pages';
                $btn_text = $post_type === 'post' ? 'Show Posts' : 'Show Pages';

                ob_start();
                ?>
                <div style="width:100%; border:1px solid #f1f1f1; float:left; box-sizing: border-box;margin-bottom:10px;">
                    <div style="box-sizing: border-box; margin: 1px; background-color: #f1f1f1;"><h2>Choose what to
                            export</h2></div>
                </div>
                <div style="clear: both;"></div>
                <div style="width:100%; border:1px solid #f1f1f1; float:left; padding:10px 10px 0 10px;margin-bottom:10px; box-sizing: border-box;">
                    <fieldset>
                        <legend class="screen-reader-text"><span>input type="radio"</span></legend>
                        <div class="wpvivid-element-space-bottom wpvivid-element-space-right" style="float: left;">
                            <label>
                                <input type="radio" option="export" name="contain" value="list"
                                       checked/><?php _e('Filter Posts/Pages'); ?>
                            </label>
                        </div>
                        <div style="clear: both;"></div>
                    </fieldset>

                    <div id="wpvivid_export_custom" style="margin-bottom: 10px;">
                        <table id="wpvivid_post_selector" class="wp-list-table widefat plugins"
                               style="width:100%; border:1px solid #f1f1f1;">
                            <tbody>
                            <?php
                            if ($post_type !== 'page') {
                                ?>
                                <tr>
                                    <td class="plugin-title column-primary">
                                        <div class="wpvivid-storage-form regular-text">
                                            <?php
                                            wp_dropdown_categories(
                                                array(
                                                    'class' => 'regular-text',
                                                    'show_option_all' => __('All Categories')
                                                )
                                            );
                                            ?>
                                        </div>
                                    </td>
                                    <td class="column-description desc">
                                        <div class="wpvivid-storage-form-desc">
                                            <i>Export <?php _e($descript_type); ?> of all categories or a specific
                                                category.</i>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td class="plugin-title column-primary">
                                    <div class="wpvivid-storage-form regular-text">
                                        <?php
                                        $authors = $wpdb->get_col("SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = '$post_type'");
                                        wp_dropdown_users(
                                            array(
                                                'class' => 'regular-text',
                                                'include' => $authors,
                                                'name' => 'post_author',
                                                'multi' => true,
                                                'show_option_all' => __('All Authors'),
                                                'show' => 'display_name_with_login',
                                            )
                                        );
                                        ?>
                                    </div>
                                </td>
                                <td class="column-description desc">
                                    <div class="wpvivid-storage-form-desc">
                                        <i>Export <?php _e($descript_type); ?> of all authors or a specific author.</i>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="plugin-title column-primary">
                                    <div class="wpvivid-storage-form regular-text">
                                        <label for="post-start-date" class="label-responsive"
                                               style="display: block;"></label>
                                        <select class="regular-text" name="post_start_date" id="post-start-date">
                                            <option value="0"><?php _e('&mdash; Select &mdash;'); ?></option>
                                            <?php $this->export_date_options($post_type); ?>
                                        </select>
                                    </div>
                                </td>
                                <td class="column-description desc">
                                    <div class="wpvivid-storage-form-desc">
                                        <i>Export <?php _e($descript_type); ?> published after this date.</i>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="plugin-title column-primary">
                                    <div class="wpvivid-storage-form regular-text">
                                        <label for="post-end-date" class="label-responsive"
                                               style="display: block;"></label>
                                        <select class="regular-text" name="post_end_date" id="post-end-date">
                                            <option value="0"><?php _e('&mdash; Select &mdash;'); ?></option>
                                            <?php $this->export_date_options($post_type); ?>
                                        </select>
                                    </div>
                                </td>
                                <td class="column-description desc">
                                    <div class="wpvivid-storage-form-desc">
                                        <i>Export <?php _e($descript_type); ?> published before this date.</i>
                                    </div>
                                </td>
                            </tr>

                            <tr style="display: none;">
                                <td class="plugin-title column-primary">
                                    <div class="wpvivid-storage-form">
                                        <input type="text" class="regular-text" id="post-search-id-input" name="post-id"
                                               autocomplete="off" value=""/>
                                    </div>
                                </td>
                                <td class="column-description desc">
                                    <div class="wpvivid-storage-form-desc">
                                        <i>Enter a <?php _e($post_type); ?> ID.(optional)</i>
                                    </div>
                                </td>
                            </tr>

                            <tr style="display: none;">
                                <td class="plugin-title column-primary">
                                    <div class="wpvivid-storage-form">
                                        <input type="text" class="regular-text" id="post-search-title-input"
                                               name="post-title" autocomplete="off" value=""/>
                                    </div>
                                </td>
                                <td class="column-description desc">
                                    <div class="wpvivid-storage-form-desc">
                                        <i>Enter a <?php _e($post_type); ?> title.(optional)</i>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="plugin-title column-primary">
                                    <div class="wpvivid-storage-form">
                                        <input class="button-primary" id="wpvivid-post-query-submit" type="submit"
                                               name="<?php echo $post_type ?>" value="<?php echo $btn_text; ?>"/>
                                    </div>
                                </td>
                                <td class="column-description desc">
                                    <div class="wpvivid-storage-form-desc">
                                        <i>Search for <?php _e($post_type); ?>s according to the above rules.</i>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div id="wpvivid_post_list"></div>
                    </div>
                </div>

                <div style="width:100%; border:1px solid #f1f1f1; float:left; box-sizing: border-box;margin-bottom:10px;">
                    <div style="box-sizing: border-box; margin: 1px; background-color: #f1f1f1;"><h2>Comment the export
                            (optional)</h2></div>
                </div>
                <div style="clear: both;"></div>
                <div style="width:100%; border:1px solid #f1f1f1; float:left; padding:10px 10px 0 10px;margin-bottom:10px; box-sizing: border-box;">
                    <div>
                        <div class="wpvivid-element-space-bottom wpvivid-text-space-right"
                             style="float: left; padding-top: 6px;">Comment the export:
                        </div>
                        <div class="wpvivid-element-space-bottom wpvivid-text-space-right" style="float: left;">
                            <input type="text" option="export" name="post_comment" id="wpvivid_set_post_comment"
                                   onkeyup="value=value.replace(/[^a-zA-Z0-9]/g,'')"
                                   onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')"/>
                        </div>
                        <div class="wpvivid-element-space-bottom wpvivid-text-space-right"
                             style="float: left; padding-top: 6px;">Only letters (except for wpvivid) and numbers are
                            allowed.
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                    <div>
                        <div class="wpvivid-element-space-bottom wpvivid-text-space-right" style="float: left;">
                            Sample:
                        </div>
                        <div class="wpvivid-element-space-bottom" style="float: left;">
                            <div class="wpvivid-element-space-bottom" style="display: inline;"
                                 id="wpvivid_post_comment">*
                            </div>
                            <div class="wpvivid-element-space-bottom" style="display: inline;">
                                _wpvivid-5dbf8d6a5f133_2019-11-08-03-15_export_<?php _e($post_type); ?>.zip
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>

                <div>
                    <input class="button-primary" id="wpvivid_start_export" type="submit"
                           name="<?php echo $post_type; ?>" value="Export and Download"
                           style="pointer-events: none; opacity: 0.4;">
                </div>
                <?php

                $html = ob_get_clean();
                $ret['result'] = 'success';
                $ret['html'] = $html;
            } else {
                $ret['result'] = 'failed';
                $ret['error'] = 'not set post type';
            }
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function export_now(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try
        {
            if (!isset($_POST['task_id']) || empty($_POST['task_id']) || !is_string($_POST['task_id']))
            {
                $ret['result'] = 'failed';
                $ret['error'] = __('Error occurred while parsing the request data. Please try to run export task again.', 'wpvivid');
                echo json_encode($ret);
                die();
            }

            $task_id = sanitize_key($_POST['task_id']);

            if(WPvivid_Exporter_taskmanager_Ex::is_tasks_running())
            {
                $ret['result'] = 'failed';
                $ret['error'] = __('A task is already running. Please wait until the running task is complete, and try again.', 'wpvivid');
                echo json_encode($ret);
                die();
            }

            $this->export_post($task_id);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function prepare_export_post(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (isset($_POST['post_type']) && isset($_POST['export_data'])) {
                $post_type = sanitize_text_field($_POST['post_type']);
                $json_export = sanitize_text_field($_POST['export_data']);
                $json_export = stripslashes($json_export);
                $export_data = json_decode($json_export, true);

                $post_ids = array();
                $posts_ids = array();

                if (isset($export_data['all_page']) && $export_data['all_page'] == 1) {
                    $list_cache = get_option('wpvivid_list_cache', array());
                    foreach ($list_cache as $id => $post) {
                        $post['checked'] = 1;
                        $post_ids[$id] = $post;
                    }
                } else {
                    if (isset($export_data['post_ids']) && !empty($export_data['post_ids'])) {
                        $post_ids = $export_data['post_ids'];
                    }
                }

                foreach ($post_ids as $id => $checked) {
                    if ($checked) {
                        $posts_ids[] = $id;
                    }
                }

                if (empty($posts_ids)) {
                    $ret['result'] = 'failed';
                    $ret['error'] = __('Empty post id', 'wpvivid');
                    echo json_encode($ret);
                    die();
                }
                if (WPvivid_Exporter_taskmanager_Ex::is_tasks_running()) {
                    $ret['result'] = 'failed';
                    $ret['error'] = __('A task is already running. Please wait until the running task is complete, and try again.', 'wpvivid');
                    echo json_encode($ret);
                    die();
                }

                $export_task = new WPvivid_Exporter_task_Ex();

                $options['post_ids'] = $posts_ids;
                $options['post_type'] = $post_type;
                $options['post_comment'] = $export_data['post_comment'];

                $ret = $export_task->new_backup_task($options);
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function list_tasks(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            $ret['result'] = 'success';
            $ret['show'] = false;
            $tasks = WPvivid_Exporter_taskmanager_Ex::get_tasks();
            foreach ($tasks as $task) {
                $this->task_monitor($task['id']);
                $task = WPvivid_Exporter_taskmanager_Ex::get_task($task['id']);
                $status = WPvivid_Exporter_taskmanager_Ex::get_backup_task_status($task['id']);

                $ret['show'] = true;
                $ret['completed'] = false;
                $ret['error'] = false;
                if ($status['str'] == 'running' || $status['str'] == 'no_responds' || $status['str'] == 'ready') {
                    $ret['continue'] = 1;
                } else {
                    $ret['continue'] = 0;
                    $ret['show'] = false;
                }

                $progress = WPvivid_Exporter_taskmanager_Ex::get_backup_tasks_progress($task['id']);
                $ret['percent'] = '<div class="action-progress-bar-percent"  style="height:24px;width:' . (int)$progress['progress'] . '%;"></div>';
                $ret['doing'] = $task['data']['doing'] = 'export';
                if ($status['str'] == 'ready') {
                    $ret['doing'] = __('Ready to export. Progress: 0%, running time: 0 second.', 'wpvivid');
                } else if ($status['str'] == 'running') {
                    $ret['doing'] = __(' Progress: ', 'wpvivid') . $progress['descript'] . __(', running time: ', 'wpvivid') . $progress['running_time'];
                } else if ($status['str'] == 'wait_resume') {
                    $ret['doing'] = 'Task ' . $task['id'] . ' timed out, the export task will retry in ' . $task['data']['next_resume_time'] . ' seconds, retry times: ' . $task['status']['resume_count'] . '.';
                } else if ($status['str'] == 'no_responds') {
                    $ret['doing'] = __('The export task is not responding.', 'wpvivid');
                } else if ($status['str'] == 'completed') {
                    $file_name = $task['data']['export']['export_info']['file_name'];
                    $file_size = $task['data']['export']['export_info']['size'];
                    if ($task['options']['backup_options']['post_type'] === 'post') {
                        $post_type = 'posts';
                    } else {
                        $post_type = 'pages';
                    }
                    $msg = '<div style="margin-bottom: 10px;">The export task is completed and the automatic download starts. If the automatic download didn\'t run, please click <a style="cursor:pointer;" onclick="wpvivid_download_export(\'' . $file_name . '\', \'' . $file_size . '\');">here</a> to download.</div>';
                    $msg .= '<div style="margin-bottom: 10px;">The count of exported ' . $post_type . ': ' . $task['data']['export']['export_info']['post_count'] . '.</div>';
                    $msg .= '<div style="margin-bottom: 10px;">File name: ' . $file_name . '.</div>';
                    $msg .= '<div>File size: ' . size_format($file_size, 2) . '.</div>';
                    /*$ret['doing']=__('<div class="notice notice-success is-dismissible inline"><p>Export task have been completed, task id: '.$task['id'].'</p>
                                        <button type="button" class="notice-dismiss" onclick="click_dismiss_notice(this);">
                                        <span class="screen-reader-text">Dismiss this notice.</span>
                                        </button>
                                        </div>');*/
                    $ret['completed'] = true;
                    $ret['file_name'] = $file_name;
                    $ret['file_size'] = $file_size;
                    $ret['doing'] = $msg;
                } else if ($status['str'] == 'error') {
                    $ret['doing'] = 'Export error: ' . $task['status']['error'];
                    $ret['doing'] = __('<div class="notice notice-error is-dismissible inline"><p>Export error: ' . $task['status']['error'] . '</p></div>');
                    $ret['error'] = true;
                }

                if ($ret['completed'] || $ret['error']) {
                    WPvivid_Exporter_taskmanager_Ex::delete_task($task['id']);
                }
            }
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_list(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (!isset($_POST['post_type']) && !isset($_POST['cat']) && !isset($_POST['authors']) && !isset($_POST['post_start_date']) && !isset($_POST['post_end_date'])) {
                die();
            }

            if (isset($_POST['post_ids']) && !empty($_POST['post_ids'])) {
                $select_post_id = (int)$_POST['post_ids'];
            } else {
                $select_post_id = 0;
            }

            if (isset($_POST['post_title']) && !empty($_POST['post_title'])) {
                $post_title = $_POST['post_title'];
            } else {
                $post_title = '';
            }
            //

            $post_type = $_POST['post_type'];
            if (isset($_POST['cat'])) {
                $cat = (int)$_POST['cat'];
            }
            $author = (int)$_POST['authors'];
            $post_start_date = $_POST['post_start_date'];
            $post_end_date = $_POST['post_end_date'];


            global $wpdb;

            $where = $wpdb->prepare("post_type ='%s'", $post_type);
            $join = '';
            if (isset($_POST['cat'])) {
                if ($term = term_exists($cat, 'category')) {
                    $join = "INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
                    $where .= $wpdb->prepare(" AND {$wpdb->term_relationships}.term_taxonomy_id = %d", $term['term_taxonomy_id']);
                }
            }
            if ($author) {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_author = %d", $author);
            }
            if ($post_start_date) {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_date >= %s", date('Y-m-d', strtotime($post_start_date)));
            }
            if ($post_end_date) {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_date < %s", date('Y-m-d', strtotime('+1 month', strtotime($post_end_date))));
            }
            if ($select_post_id) {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.ID = %d", $select_post_id);
            }
            if ($post_title) {
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($post_title) . '%');
            }

            $posts_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} $join WHERE $where");

            asort($posts_ids);

            $list_cache = array();
            foreach ($posts_ids as $id) {
                $post_id['id'] = $id;
                $post_id['checked'] = 0;
                $list_cache[$id] = $post_id;
            }
            WPvivid_Setting::update_option('wpvivid_list_cache', $list_cache);
            $page = 1;

            $arg['screen'] = $post_type;
            $myListTable = new WPvivid_Post_List_Ex($arg);
            $myListTable->set_post_ids($list_cache, $page);
            $myListTable->prepare_items();
            ob_start();
            $myListTable->display();
            $rows = ob_get_clean();
            $ret['result'] = 'success';
            $ret['rows'] = $rows;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_list_page(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (!isset($_POST['post_type']) && !isset($_POST['page'])) {
                die();
            }

            $list_cache = get_option('wpvivid_list_cache', array());

            WPvivid_Setting::update_option('wpvivid_list_cache', $list_cache);

            $page = $_POST['page'];

            $post_type = $_POST['post_type'];
            $arg['screen'] = $post_type;

            $myListTable = new WPvivid_Post_List_Ex($arg);
            $myListTable->set_post_ids($list_cache, $page);
            $myListTable->prepare_items();
            ob_start();
            $myListTable->display();
            $rows = ob_get_clean();

            $ret['result'] = 'success';
            $ret['rows'] = $rows;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_import_list_page(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (!isset($_POST['page'])) {
                die();
            }
            $page = $_POST['page'];

            $backups = get_option('wpvivid_import_list_cache');

            $display_list = new WPvivid_Export_List();
            $display_list->set_parent('wpvivid_import_list');
            $display_list->set_list($backups, $page);
            $display_list->prepare_items();
            ob_start();
            $display_list->display();
            $html = ob_get_clean();

            $ret['result'] = 'success';
            $ret['rows'] = $html;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_export_list(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (isset($_POST['export_id'])) {
                $id = sanitize_key($_POST['export_id']);
                $list = get_option('wpvivid_import_list_cache', array());
                if (empty($list)) {
                    $ret['result'] = 'success';
                } else {
                    if (isset($list[$id])) {
                        $item = $list[$id];
                        if (isset($item['export'])) {
                            foreach ($item['export'] as $file) {
                                $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . WPvivid_Setting::get_backupdir() . DIRECTORY_SEPARATOR . WPVIVID_IMPORT_EXPORT_DIR_ADDON . DIRECTORY_SEPARATOR . $file['file_name'];
                                @unlink($path);
                            }
                        }
                        unset($list[$id]);
                        WPvivid_Setting::update_option('wpvivid_import_list_cache', $list);
                        $ret['result'] = 'success';
                    } else {
                        $ret['result'] = 'success';
                    }
                }
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function start_import(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        $this->end_shutdown_function = false;
        register_shutdown_function(array($this,'deal_import_shutdown_error'));
        try
        {
            if (isset($_POST['file_name']) && !empty($_POST['file_name']) && is_string($_POST['file_name']))
            {
                $files=array();
                $options=array();
                $files[]=$_POST['file_name'];
                $options['user']=0;
                if(isset($_POST['user']))
                {
                    $options['user']=$_POST['user'];
                }
                $options['update_exist']=0;
                if(isset($_POST['update_exist']))
                {
                    $options['update_exist']=$_POST['update_exist'];
                }

                $task_id=$this->get_file_id($_POST['file_name']);
                WPvivid_Impoter_taskmanager::new_task($task_id, $files,$options);
                $import_log = new WPvivid_import_data();
                $import_log->wpvivid_create_import_log();
                $import_log->wpvivid_write_import_log('Start importing', 'notice');
                $this->flush($task_id);
                WPvivid_Impoter_taskmanager::update_import_task_status($task_id, 'running', true);
                $importer = new WPvivid_media_importer();
                $ret = $importer->import($task_id);
                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An error has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            error_log($message);
            WPvivid_Exporter_taskmanager_Ex::update_backup_task_status($task_id,false,'error',false,false,$message);
            global $wpvivid_plugin;
            $wpvivid_plugin->wpvivid_log->WriteLog($message,'error');
            $this->end_shutdown_function=true;
            die();
        }
        $this->end_shutdown_function=true;
        die();
    }

    public function wpvivid_download_export_backup(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try{
            if(isset($_REQUEST['file_name']) && !empty($_REQUEST['file_name']) && is_string($_REQUEST['file_name']) &&
                isset($_REQUEST['file_size']) && !empty($_REQUEST['file_size']) && is_string($_REQUEST['file_size'])){
                $file_name = $_REQUEST['file_name'];
                $file_size = intval($_REQUEST['file_size']);

                $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.WPvivid_Setting::get_backupdir().DIRECTORY_SEPARATOR.WPVIVID_IMPORT_EXPORT_DIR_ADDON.DIRECTORY_SEPARATOR.$file_name;
                if (file_exists($path)) {
                    if (session_id()) {
                        session_write_close();
                    }
                    $size = filesize($path);
                    if($size === $file_size) {
                        if (!headers_sent()) {
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/zip');
                            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                            header('Cache-Control: must-revalidate');
                            header('Content-Length: ' . $size);
                            header('Content-Transfer-Encoding: binary');
                        }
                        if ($size < 1024 * 1024 * 60) {
                            ob_end_clean();
                            readfile($path);
                            exit;
                        } else {
                            ob_end_clean();
                            $download_rate = 1024 * 10;
                            $file = fopen($path, "r");
                            while (!feof($file)) {
                                @set_time_limit(20);
                                // send the current file part to the browser
                                print fread($file, round($download_rate * 1024));
                                // flush the content to the browser
                                flush();
                                // sleep one second
                                sleep(1);
                            }
                            fclose($file);
                            exit;
                        }
                    }
                    else{
                        $admin_url = admin_url();
                        echo __('File size not match. please <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-export-import').'">retry</a> again.');
                        die();
                    }
                }

                $admin_url = admin_url();
                echo __('File not found. please <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-export-import').'">retry</a> again.');
                die();
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function check_import_file(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            if (isset($_POST['file_name'])) {
                $ret = $this->wpvivid_check_import_file_name($_POST['file_name']);
            } else {
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error'] = 'Failed to post file name.';
            }

            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function upload_import_files(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            $options['test_form'] = true;
            $options['action'] = 'wpvivid_upload_import_files';
            $options['test_type'] = false;
            $options['ext'] = 'zip';
            $options['type'] = 'application/zip';

            add_filter('upload_dir', array($this, 'upload_import_dir'));

            $status = wp_handle_upload($_FILES['async-upload'], $options);

            remove_filter('upload_dir', array($this, 'upload_import_dir'));

            if (isset($status['error'])) {
                echo json_encode(array('result' => WPVIVID_PRO_FAILED, 'error' => $status['error']));
                exit;
            }

            $file_name = basename($_POST['name']);

            if (isset($_POST['chunks']) && isset($_POST['chunk'])) {
                $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . WPvivid_Setting::get_backupdir() . DIRECTORY_SEPARATOR . WPVIVID_IMPORT_EXPORT_DIR_ADDON . DIRECTORY_SEPARATOR;
                rename($status['file'], $path . $file_name . '_' . $_POST['chunk'] . '.tmp');
                $status['file'] = $path . $file_name . '_' . $_POST['chunk'] . '.tmp';
                if ($_POST['chunk'] == $_POST['chunks'] - 1) {
                    $file_handle = fopen($path . $file_name, 'wb');
                    if ($file_handle) {
                        for ($i = 0; $i < $_POST['chunks']; $i++) {
                            $chunks_handle = fopen($path . $file_name . '_' . $i . '.tmp', 'rb');
                            if ($chunks_handle) {
                                while ($line = fread($chunks_handle, 1048576 * 2)) {
                                    fwrite($file_handle, $line);
                                }
                                fclose($chunks_handle);
                                @unlink($path . $file_name . '_' . $i . '.tmp');
                            }
                        }
                        fclose($file_handle);
                    }
                }
            }
            echo json_encode(array('result' => WPVIVID_PRO_SUCCESS));
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function upload_import_file_complete(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            $ret['html'] = false;
            if (isset($_POST['files'])) {
                $files = stripslashes($_POST['files']);
                $files = json_decode($files, true);
                if (is_null($files)) {
                    $ret['result'] = WPVIVID_PRO_FAILED;
                    $ret['error'] = 'Failed to decode files.';
                    echo json_encode($ret);
                    die();
                }

                $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . WPvivid_Setting::get_backupdir() . DIRECTORY_SEPARATOR . WPVIVID_IMPORT_EXPORT_DIR_ADDON . DIRECTORY_SEPARATOR;

                //if(preg_match('/wpvivid-.*_.*_to_.*\.zip$/',$files[0]['name']))
                //{
                $data = array();
                $check_result = true;
                foreach ($files as $file) {
                    $res = $this->check_is_import_file($path . $file['name']);
                    if ($res['result'] == 'success') {
                        $add_file['file_name'] = $file['name'];
                        $add_file['size'] = filesize($path . $file['name']);
                        $add_file['export_type'] = $res['export_type'];
                        $add_file['export_comment'] = $res['export_comment'];
                        $add_file['posts_count'] = $res['posts_count'];
                        $add_file['media_size'] = size_format($res['media_size'], 2);
                        $add_file['time'] = $res['time'];
                        $data[] = $add_file;
                    } else {
                        $check_result = false;
                    }
                }

                if ($check_result === true) {
                    $ret['result'] = WPVIVID_PRO_SUCCESS;
                    $ret['data'] = $data;
                } else {
                    $ret['result'] = WPVIVID_PRO_FAILED;
                    $ret['error'] = 'Upload file failed.';
                    foreach ($files as $file) {
                        $this->clean_tmp_files($path, $file['name']);
                        @unlink($path . $file['name']);
                    }
                }
                /*}
                else
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='The file is not created by WPvivid backup plugin.';
                }*/
            } else {
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error'] = 'Failed to post file name.';
            }
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_import_progress(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try
        {
            $tasks=WPvivid_Impoter_taskmanager::get_tasks();
            foreach ($tasks as $task)
            {
                WPvivid_Impoter_taskmanager::get_task($task['id']);
                $import_log = new WPvivid_import_data();
                $ret['result'] = 'success';
                $ret['status'] = WPvivid_Impoter_taskmanager::get_import_task_status($task['id']);
                if ($ret['status'] === 'error')
                {
                    WPvivid_Impoter_taskmanager::delete_task($task['id']);
                }
                if($ret['status'] === 'completed')
                {
                    WPvivid_Impoter_taskmanager::delete_task($task['id']);
                }
                $ret['log'] = $import_log->get_log_content();
                echo json_encode($ret);
                die();
            }
            $ret['result'] = 'success';
            $ret['status'] ='wait';
            $ret['log']='';
            echo json_encode($ret);
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function wpvivid_scan_import_folder(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . WPvivid_Setting::get_backupdir() . DIRECTORY_SEPARATOR . WPVIVID_IMPORT_EXPORT_DIR_ADDON . DIRECTORY_SEPARATOR;

            $data = array();
            $count = 0;
            if (is_dir($path)) {
                $handler = opendir($path);
                if($handler!==false)
                {
                    while (($filename = readdir($handler)) !== false) {
                        if ($filename != "." && $filename != "..") {
                            $count++;

                            if (is_dir($path . $filename)) {
                                continue;
                            } else {

                                $res = $this->check_is_import_file($path . $filename);
                                if ($res['result'] == 'success') {
                                    $add_file['file_name'] = $filename;
                                    $add_file['size'] = filesize($path . $filename);
                                    $add_file['export_type'] = $res['export_type'];
                                    $add_file['export_comment'] = $res['export_comment'];
                                    $add_file['posts_count'] = $res['posts_count'];
                                    $add_file['media_size'] = size_format($res['media_size'], 2);
                                    $add_file['time'] = $res['time'];
                                    $data[$this->get_file_id($filename)] = $add_file;
                                }
                            }
                        }
                    }
                    if ($handler)
                        @closedir($handler);
                }

            } else {
                global $wpvivid_plugin;
                $wpvivid_plugin->wpvivid_log = new WPvivid_Log();
                $id = uniqid('wpvivid-');
                $wpvivid_plugin->wpvivid_log->CreateLogFile($id . '_scan', 'no_folder', 'scan');
                $wpvivid_plugin->wpvivid_log->WriteLogHander();
                $wpvivid_plugin->wpvivid_log->WriteLog('Failed to get local storage directory.', 'notice');
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error'] = 'Failed to get local storage directory.';
            }
            WPvivid_Setting::update_option('wpvivid_import_list_cache', $data);
            $page = 1;
            $display_list = new WPvivid_Export_List();
            $display_list->set_parent('wpvivid_import_list');
            $display_list->set_list($data, $page);
            $display_list->prepare_items();
            ob_start();
            $display_list->display();
            $html = ob_get_clean();
            $ret['html'] = $html;
            $ret['data'] = $data;
            $ret['result'] = WPVIVID_PRO_SUCCESS;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function calc_import_folder_size(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            global $wpvivid_plugin;
            $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . WPvivid_Setting::get_backupdir() . DIRECTORY_SEPARATOR . WPVIVID_IMPORT_EXPORT_DIR_ADDON . DIRECTORY_SEPARATOR;
            $bytes_total = 0;
            $path = realpath($path);
            if ($path !== false && $path != '' && file_exists($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    $bytes_total += $object->getSize();
                }
            }
            $ret['result'] = WPVIVID_PRO_SUCCESS;
            $ret['size'] = $wpvivid_plugin->formatBytes($bytes_total);
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function clean_import_folder(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try {
            $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . WPvivid_Setting::get_backupdir() . DIRECTORY_SEPARATOR . WPVIVID_IMPORT_EXPORT_DIR_ADDON . DIRECTORY_SEPARATOR;
            if (is_dir($path)) {
                $handler = opendir($path);
                if($handler!==false)
                {
                    while (($filename = readdir($handler)) !== false) {
                        if ($filename != "." && $filename != "..") {
                            if (is_dir($path . $filename)) {
                                continue;
                            } else {
                                $res = $this->check_is_import_file($path . $filename);
                                if ($res['result'] == 'success') {
                                    @unlink($path . $filename);
                                }
                            }
                        }
                    }
                    @closedir($handler);
                }

            }

            $data = array();
            WPvivid_Setting::update_option('wpvivid_import_list_cache', $data);
            $page = 1;
            $display_list = new WPvivid_Export_List();
            $display_list->set_parent('wpvivid_import_list');
            $display_list->set_list($data, $page);
            $display_list->prepare_items();
            ob_start();
            $display_list->display();
            $html = ob_get_clean();
            $ret['html'] = $html;
            $ret['data'] = $data;
            $ret['result'] = WPVIVID_PRO_SUCCESS;

            global $wpvivid_plugin;
            $bytes_total = 0;
            $path = realpath($path);
            if ($path !== false && $path != '' && file_exists($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    $bytes_total += $object->getSize();
                }
            }
            $ret['size'] = $wpvivid_plugin->formatBytes($bytes_total);

            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function search_and_replace()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try
        {
            if(isset($_POST['options']) && !empty($_POST['options']) && isset($_POST['tables']) && !empty($_POST['tables']))
            {
                $json = $_POST['options'];
                $json = stripslashes($json);
                $options = json_decode($json, true);
                if (is_null($options))
                {
                    die();
                }
                global $wpdb;
                $this->search_string=$options['search_for'];
                $this->replace_string=$options['replace_with'];
                $replace_execution_time = intval($options['replace_execution_time']);
                set_time_limit($replace_execution_time);
                $this->new_prefix=$wpdb->base_prefix;
                $tables = $_POST['tables'];

                global $wpvivid_plugin;
                $wpvivid_plugin->restore_data= new WPvivid_restore_data();
                $wpvivid_plugin->restore_data->restore_log=new WPvivid_Log();
                $wpvivid_plugin->restore_data->restore_log->CreateLogFile($wpvivid_plugin->restore_data->restore_log_file,'has_folder','restore');

                $task=array();
                $task['status']='start';
                update_option('wpvivid_search_replace_task',$task,'no');

                $this->db_method=new WPvivid_Restore_DB_Method_Addon();
                $this->db_method->set_skip_query(0);
                $ret=$this->db_method->connect_db();
                if($ret['result']==WPVIVID_PRO_FAILED)
                {
                    return $ret;
                }

                $this->db_method->check_max_allow_packet($wpvivid_plugin->restore_data->restore_log);
                $this->db_method->init_sql_mode();

                foreach($tables as $table)
                {
                    $this->search_and_replace_table($table);
                }

                $task=get_option('wpvivid_search_replace_task',array());
                $task['status']='finish';
                update_option('wpvivid_search_replace_task',$task,'no');

                unset($this->db_method);

                $ret['result']=WPVIVID_PRO_SUCCESS;
                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_replace_progress()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-export');
        try
        {
            $ret['result'] = 'success';
            $task=get_option('wpvivid_search_replace_task',array());

            if(!empty($task))
            {
                if($task['status']=='finish')
                {
                    update_option('wpvivid_search_replace_task',array(),'no');
                }
                global $wpvivid_plugin;
                $wpvivid_plugin->restore_data = new WPvivid_restore_data();
                if (file_exists($wpvivid_plugin->restore_data->restore_log_file))
                {
                    $ret['result'] = 'success';
                    $ret['status'] = $task['status'];
                    $ret['log'] = $wpvivid_plugin->restore_data->get_log_content();
                } else {
                    $ret['result'] = 'failed';
                    $ret['error'] = __('The replace file not found. Please verify the file exists.', 'wpvivid');
                }
            }
            else {
                $ret['status'] = 'wait';
                $ret['log'] = '';
            }
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }
    /***** export import ajax end *****/

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Export/Import Page', 'wpvivid' ); ?></h1>
            <div id="wpvivid_export_notice"></div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-randomize wpvivid-dashicons-large wpvivid-dashicons-green"></span><span class="wpvivid-page-title">Export and Import</span></p>
                                        <span class="about-description">Export/Import posts or pages with images in bulk</span>
                                    </div>
                                    <div class="wpvivid-welcome-bar-right">
                                        <p></p>
                                        <div style="float:right;">
                                            <span>Local Time:</span>
                                            <span>
									        	<a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'options-general.php'); ?>">
                                                    <?php
                                                    $offset=get_option('gmt_offset');
                                                    echo date("l, F-d-Y H:i",time()+$offset*60*60);
                                                    ?>
                                                </a>
									        </span>
                                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
									        	<div class="wpvivid-left">
										        	<!-- The content you need -->
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span><strong>Exported/Imported files will be temporarily stored under folder</strong></span><span><code>.../wp-content/<?php _e(WPvivid_Setting::get_backupdir()); ?>/ImportandExport</code></span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-upload';
                                    $args['span_style']='color:#007cba; padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['export']['title']='Export';
                                    $tabs['export']['slug']='export';
                                    $tabs['export']['callback']=array($this, 'output_export');
                                    $tabs['export']['args']=$args;

                                    $args['span_class']='dashicons dashicons-download';
                                    $args['span_style']='color:red;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $tabs['import']['title']='Import';
                                    $tabs['import']['slug']='import';
                                    $tabs['import']['callback']=array($this, 'output_import');
                                    $tabs['import']['args']=$args;

                                    $args['span_class']='dashicons dashicons-code-standards wpvivid-dashicons-green';
                                    $args['span_style']='padding-right:0.5em;margin-top:0.1em;';
                                    $tabs['tool_url_replace']['title']='Url Replacing Tool';
                                    $tabs['tool_url_replace']['slug']='tool_url_replace';
                                    $tabs['tool_url_replace']['callback']=array($this, 'output_url_replace_page');
                                    $tabs['tool_url_replace']['args']=$args;

                                    $args['span_class']='dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey';
                                    $args['span_style']='padding-right:0.5em;margin-top:0.1em;';
                                    $tabs['export_import_log_list']['title']='Logs';
                                    $tabs['export_import_log_list']['slug']='export_import_log_list';
                                    $tabs['export_import_log_list']['callback']=array($this, 'output_export_import_log_list');
                                    $tabs['export_import_log_list']['args']=$args;

                                    $args['span_class']='';
                                    $args['span_style']='';
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['open_log']['title']='Logs';
                                    $tabs['open_log']['slug']='open_log';
                                    $tabs['open_log']['callback']=array($this, 'output_open_log');
                                    $tabs['open_log']['args']=$args;

                                    foreach ($tabs as $key=>$tab)
                                    {
                                        $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                                    }

                                    $this->main_tab->display();
                                    ?>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- sidebar -->
                    <?php
                    do_action( 'wpvivid_backup_pro_add_sidebar' );
                    ?>

                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ()
            {
                <?php
                if(isset($_REQUEST['import_page']))
                {
                    ?>
                    jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'import', 'import' ]);
                    <?php
                }

                if(isset($_REQUEST['url_replace']))
                {
                    ?>
                    jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'tool_url_replace', 'tool_url_replace' ]);
                    <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function output_export()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom:1em;">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span>Try to select fewer items when you are facing a shortage of server resources (typically presented as a timeout error).</span>
        </div>

        <div style="width:100%; border:1px solid #f1f1f1; float:left; box-sizing: border-box;margin-bottom:10px;">
            <div style="box-sizing: border-box; margin: 1px; background-color: #f1f1f1;"><h2><?php _e('Choose post type', 'wpvivid'); ?></h2></div>
        </div>
        <div style="clear: both;"></div>
        <div class="postbox wpvivid-element-space-bottom">
            <div class="wpvivid-export-type-provider wpvivid-export-type-post wpvivid-export-type-provider-active" onclick="wpvivid_select_export_type('post');">
                <?php _e('Post', 'wpvivid'); ?>
            </div>
            <div class="wpvivid-export-type-provider wpvivid-export-type-page" onclick="wpvivid_select_export_type('page');">
                <?php _e('Page', 'wpvivid'); ?>
            </div>
            <div class="wpvivid-export-type-provider">
                <?php _e('More post types coming soon...', 'wpvivid'); ?>
            </div>
        </div>

        <div id="wpvivid_export_page">
            <input class="button-primary wpvivid-button-export-archieve" type="submit" name="post" value="Next Step" />
        </div>
        <div class="postbox" id="wpvivid_export_task_progress" style="display: none; margin-top: 10px; margin-bottom: 0;">
            <div class="action-progress-bar" id="wpvivid_export_bar_percent">
                <div class="action-progress-bar-percent" style="width:0; height:24px;"></div>
            </div>
            <div style="clear: both;"></div>
            <div style="margin-left:10px; float: left; width:100%;"><p id="wpvivid_export_current_doing"></p></div>
            <div style="clear: both;"></div>
        </div>
        <div class="postbox" id="wpvivid_export_summary" style="display: none; margin-top: 10px; margin-bottom: 0; padding: 10px;"></div>

        <script>
            var export_task_id='';
            var retry_count=0;

            var current_export_type = 'post';
            function wpvivid_select_export_type(export_type){
                jQuery('.wpvivid-export-type-provider').removeClass('wpvivid-export-type-provider-active');
                jQuery('.wpvivid-export-type-'+export_type).addClass('wpvivid-export-type-provider-active');
                if(current_export_type !== export_type){
                    current_export_type = export_type;
                    var button_html = '<input class="button-primary wpvivid-button-export-archieve" type="submit" name="'+export_type+'" value="Next Step" />';
                    jQuery('#wpvivid_export_page').html(button_html);
                    jQuery('#wpvivid_export_summary').hide();
                }
            }

            function wpvivid_archieve_export_info(post_type, is_running){
                var ajax_data = {
                    'action':'wpvivid_export_post_step2',
                    'post_type': post_type
                };
                wpvivid_post_request_addon(ajax_data, function(data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery('#wpvivid_export_page').html(jsonarray.html);
                        if(is_running){
                            jQuery('#wpvivid_export_custom').hide();
                        }
                    }
                    else if (jsonarray.result === 'failed') {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_import_lock_unlock(action){
                var css_pointer_event = '';
                var css_opacity = '';
                if(action === 'lock'){
                    css_pointer_event = 'none';
                    css_opacity = '0.4';
                }
                else{
                    css_pointer_event = 'auto';
                    css_opacity = '1';
                }
                jQuery('.wpvivid-export-type-provider').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page .wpvivid-button-export-archieve').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page #wpvivid-post-query-submit').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page #wpvivid-post-research-submit').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('input:radio[name=contain]').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page #wpvivid_start_export').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_tab_export').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_tab_import').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_empty_import_folder').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_select_import_file_button').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_upload_file_list').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('.export-list-import').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_start_import').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_rechoose_import_file').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
            }

            function wpvivid_export_lock_unlock(action){
                var css_pointer_event = '';
                var css_opacity = '';
                if(action === 'lock'){
                    css_pointer_event = 'none';
                    css_opacity = '0.4';
                }
                else{
                    css_pointer_event = 'auto';
                    css_opacity = '1';
                }
                jQuery('.wpvivid-export-type-provider').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page .wpvivid-button-export-archieve').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page #wpvivid-post-query-submit').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page #wpvivid-post-research-submit').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('input:radio[name=contain]').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
                jQuery('#wpvivid_export_page #wpvivid_start_export').css({'pointer-events': css_pointer_event, 'opacity': css_opacity});
            }

            jQuery('#wpvivid_export_page').on("click", ".wpvivid-button-export-archieve", function(){
                var post_type = jQuery(this).attr('name');
                wpvivid_archieve_export_info(post_type, false);
            });

            jQuery('#wpvivid_export_page').on("click", "#wpvivid-post-research-submit",function() {
                jQuery('#wpvivid_post_selector').show();
                jQuery('#wpvivid_post_list').hide();
            });

            jQuery('#wpvivid_export_page').on("click", "#wpvivid-post-query-submit",function() {
                var post_type = jQuery('#wpvivid-post-query-submit').attr('name');
                var cat=jQuery('select[name=cat]').val();
                var authors=jQuery('select[name=post_author]').val();
                var post_start_date=jQuery('select[name=post_start_date]').val();
                var post_end_date=jQuery('select[name=post_end_date]').val();
                var post_ids=jQuery('input[name=post-id]').val();
                var post_title=jQuery('input[name=post-title]').val();
                var ajax_data = {
                    'action':'wpvivid_get_post_list',
                    'post_type': post_type,
                    'cat':cat,
                    'authors':authors,
                    'post_start_date':post_start_date,
                    'post_end_date':post_end_date,
                    'post_ids':post_ids,
                    'post_title':post_title
                };
                wpvivid_post_request_addon(ajax_data, function(data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery('#wpvivid_post_selector').hide();
                        jQuery('#wpvivid_bottom_step2').show();
                        jQuery('#wpvivid_post_list').show();
                        jQuery('#wpvivid_post_list').html(jsonarray.rows);
                    }
                    else if (jsonarray.result === 'failed') {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_export_page').on("click", 'input:radio[name=contain]',function() {
                if(jQuery(this).val()==='list') {
                    jQuery('#wpvivid_export_custom').show();
                }
                else {
                    jQuery('#wpvivid_export_custom').hide();
                }
            });

            jQuery('#wpvivid_export_page').on("keyup", '#wpvivid_set_post_comment', function(){
                var post_comment = jQuery('#wpvivid_set_post_comment').val();
                if(post_comment === ''){
                    post_comment = '*';
                    jQuery('#wpvivid_post_comment').html(post_comment);
                }
                else{
                    var reg = RegExp(/wpvivid/, 'i');
                    if (post_comment.match(reg)) {
                        jQuery('#wpvivid_set_post_comment').val('');
                        jQuery('#wpvivid_post_comment').html('*');
                        alert('You can not use word \'wpvivid\' to comment the post.');
                    }
                    else{
                        jQuery('#wpvivid_post_comment').html(post_comment);
                    }
                }
            });

            function wpvivid_check_export_status(){
                var check_status = false;
                jQuery('input[name="post[]"]').each(function (i) {
                    var id=jQuery(this).val();
                    if(jQuery(this).prop('checked')) {
                        check_status = true;
                        return;
                    }
                });
                return check_status;
            }

            jQuery('#wpvivid_export_page').on("click", '#cb-select-all-1', function() {
                if(jQuery(this).prop('checked')) {
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'auto', 'opacity': '1'});
                }
                else{
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
            });

            jQuery('#wpvivid_export_page').on("click", '#cb-select-all-2', function() {
                if(jQuery(this).prop('checked')) {
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'auto', 'opacity': '1'});
                }
                else{
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
            });

            jQuery('#wpvivid_export_page').on("click", 'input[name="post[]"]', function() {
                var check_status = wpvivid_check_export_status();
                if(check_status){
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'auto', 'opacity': '1'});
                }
                else{
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
            });

            jQuery('#wpvivid_export_page').on("click", '#wpvivid_start_export', function(){
                wpvivid_clear_notice('wpvivid_export_notice');
                jQuery('#wpvivid_export_summary').hide();
                var post_type = jQuery('#wpvivid_start_export').attr('name');

                var select_type='all';
                jQuery('input:radio[name=contain]').each(function() {
                    if(jQuery(this).prop('checked')) {
                        select_type=jQuery(this).val();
                    }
                });

                var has_item = false;
                var post_ids = {};
                jQuery('input[name="post[]"]').each(function (i) {
                    var id=jQuery(this).val();
                    if(jQuery(this).prop('checked')) {
                        post_ids[id]=1;
                        has_item = true;
                    }
                    else {
                        post_ids[id]=0;
                    }
                });

                if(select_type === 'list' && !has_item){
                    alert('Please select at least one item.');
                }
                else{
                    var post_ids_json = {
                        'post_ids': post_ids
                    };

                    jQuery('#wpvivid_export_custom').hide();

                    var export_data = wpvivid_ajax_data_transfer('export');
                    export_data = JSON.parse(export_data);
                    jQuery.extend(export_data, post_ids_json);

                    export_data = JSON.stringify(export_data);

                    var ajax_data = {
                        'action': 'wpvivid_prepare_export_post',
                        'post_type': post_type,
                        'export_data': export_data
                    };
                    wpvivid_export_lock_unlock('lock');
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            export_task_id=jsonarray.task_id;

                            var ajax_data = {
                                'action':'wpvivid_export_now',
                                'task_id':export_task_id
                            };

                            wpvivid_export_progpress();

                            wpvivid_post_request_addon(ajax_data, function(data) {
                            },function(XMLHttpRequest, textStatus, errorThrown) {
                            });
                        }
                        else if (jsonarray.result === 'failed') {
                            wpvivid_export_lock_unlock('unlock');
                            alert(jsonarray.error);
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        wpvivid_export_lock_unlock('unlock');
                        var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#wpvivid_export_page').on("click",'.first-page',function() {
                wpvivid_change_page('first');
            });

            jQuery('#wpvivid_export_page').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_change_page(page-1);
            });

            jQuery('#wpvivid_export_page').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_change_page(page+1);
            });

            jQuery('#wpvivid_export_page').on("click",'.last-page',function() {
                wpvivid_change_page('last');
            });

            jQuery('#wpvivid_export_page').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_change_page(page);
                }
            });

            jQuery('#wpvivid_export_page').on("click",'input[name="all_page"]',function() {
                if(jQuery(this).prop('checked'))
                {
                    jQuery('#wpvivid_export_page').find("input[name=all_page]").attr('checked', "checked");
                    jQuery('#wpvivid_export_custom').find("input[type=checkbox]").attr('checked', "checked");
                }
                else
                {
                    jQuery('#wpvivid_export_page').find("input[name=all_page]").prop('checked', false);
                    jQuery('#wpvivid_export_custom').find("input[type=checkbox]").attr('checked', false);
                }

                var check_status = wpvivid_check_export_status();
                if(check_status){
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'auto', 'opacity': '1'});
                }
                else{
                    jQuery('#wpvivid_start_export').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
            });

            function wpvivid_change_page(page) {
                var post_type='post';
                jQuery('input:radio[name=post_type]').each(function() {
                    if(jQuery(this).prop('checked'))
                    {
                        post_type=jQuery(this).val();
                    }
                });

                var post_ids = {};

                jQuery('input[name="post[]"]').each(function (i) {
                    var id=jQuery(this).val();
                    if(jQuery(this).prop('checked'))
                    {
                        post_ids[id]=1;
                    }
                    else
                    {
                        post_ids[id]=0;
                    }
                });

                var ajax_data = {
                    'action':'wpvivid_get_post_list_page',
                    'post_type': post_type,
                    'page': page,
                    'post_ids':post_ids
                };

                wpvivid_post_request_addon(ajax_data, function(data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery('#wpvivid_post_list').html(jsonarray.rows);
                    }
                    else if (jsonarray.result === 'failed') {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_export_progpress() {
                var ajax_data = {
                    'action': 'wpvivid_export_list_tasks',
                    'task_id': export_task_id
                };

                jQuery('#wpvivid_export_task_progress').show();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        retry_count=0;
                        export_task_id=jsonarray.task_id;
                        if(jsonarray.show)
                        {
                            jQuery('#wpvivid_export_task_progress').show();
                            jQuery('#wpvivid_export_summary').hide();
                            jQuery('#wpvivid_export_bar_percent').html(jsonarray.percent);
                            jQuery('#wpvivid_export_current_doing').html(jsonarray.doing);
                        }
                        else
                        {
                            jQuery('#wpvivid_export_task_progress').hide();
                        }

                        if(jsonarray.completed)
                        {
                            wpvivid_export_lock_unlock('unlock');
                            //jQuery('#wpvivid_export_notice').show();
                            //jQuery('#wpvivid_export_notice').append(jsonarray.doing);
                            jQuery('#wpvivid_export_summary').show();
                            jQuery('#wpvivid_export_summary').html(jsonarray.doing);
                            jQuery('html, body').animate({scrollTop: jQuery("#wpvivid_export_notice").offset().top}, 'slow');
                            wpvivid_download_export(jsonarray.file_name, jsonarray.file_size);
                        }

                        if(jsonarray.continue)
                        {
                            wpvivid_export_lock_unlock('lock');
                            setTimeout(function ()
                            {
                                wpvivid_export_progpress();
                            }, 3000);
                        }

                        if(jsonarray.error){
                            wpvivid_export_lock_unlock('unlock');
                            jQuery('#wpvivid_export_notice').show();
                            jQuery('#wpvivid_export_notice').append(jsonarray.doing);
                            jQuery('html, body').animate({scrollTop: jQuery("#wpvivid_export_notice").offset().top}, 'slow');
                        }
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    retry_count++;
                    if(retry_count<3)
                    {
                        setTimeout(function () {
                            wpvivid_export_progpress();
                        }, 3000);
                    }
                });
            }

            function wpvivid_download_export(file_name, file_size){
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_export_backup&file_name='+file_name+'&file_size='+file_size;
            }

            jQuery(document).ready(function (){
                <?php
                $task_id = false;
                $post_type = false;
                $tasks=WPvivid_Exporter_taskmanager_Ex::get_tasks();
                foreach ($tasks as $task){
                    $task_id = $task['id'];
                    $post_type = $task['options']['backup_options']['post_type'];
                    break;
                }
                ?>
                var task_id = '<?php echo $task_id; ?>';
                if(task_id != false){
                    export_task_id = task_id;
                    wpvivid_export_lock_unlock('lock');
                    wpvivid_export_progpress();
                }
            });
        </script>
        <?php
    }

    public function output_import()
    {
        $import_dir = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.WPvivid_Setting::get_backupdir().DIRECTORY_SEPARATOR.WPVIVID_IMPORT_EXPORT_DIR_ADDON;
        WPvivid_Setting::update_option('wpvivid_import_list_cache',array());
        WPvivid_Setting::update_option('wpvivid_importer_task_list', array());
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom:1em;">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span>To properly display the imported content, please make sure that the importing and exporting sites have the same environment, for example, same theme or pages built with the same page builder.</span>
        </div>
        <div style="clear: both;"></div>

        <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; margin-bottom: 10px; padding: 10px;">
            <div style="margin-right: 10px; float: left; height: 28px; line-height: 28px;"><?php _e('Imported files will be temporarily stored in directory '.$import_dir); ?></div>
            <div style="float: left;"><input class="button" type="submit" id="wpvivid_empty_import_folder" value="Delete Exported Files In Folder" onclick="wpvivid_clean_import_folder();" /></div>
            <div style="clear: both;"></div>
        </div>
        <div id="wpvivid_import_step1">
            <p>Choose an export from your computer to import: </p>
            <input class="button button-primary" type="button" id="wpvivid_select_import_file_button" value="Upload and Import" />
            <div id="wpvivid_upload_file_list" class="hide-if-no-js" style="margin-top: 10px; display: none;"></div>
            <br>
            <p>Or you can use ftp to upload the export to the directory <?php _e($import_dir); ?>. Then click the button below to scan the file to import.</p>
            <input class="button button-primary" type="button" value="Scan Uploaded Exports" onclick="wpvivid_refresh_import_list();" />
            <div class="wpvivid-export-import-block" id="wpvivid_import_list" style="margin-top: 10px; display: none;"></div>
        </div>
        <div id="wpvivid_import_step2" style="display: none;">
            <h3>The importing file info</h3>
            <div id="wpvivid_import_file_data">
            </div>
            <h3>Assign Author</h3>
            <div>
                Select an existing author:
                <?php wp_dropdown_users( array( 'name' => "user_map", 'multi' => true, 'show_option_all' => __( '- Select -', 'wpvivid' ) ) );?>
            </div>
            <h3>Import Setting</h3>
            <div style="margin-bottom: 10px;">
                <label>
                    <input type="checkbox" id="wpvivid_overwrite_existing" />
                    <span><strong id="wpvivid_import_type">Overwrite existing pages</strong></span>
                </label>
            </div>
            <div style="margin-bottom: 10px;">
                <span>With this option checked, pages/posts already existing will be overwritten with the updated ones in an import.</span>
            </div>
            <input class="button button-primary" type="button" id="wpvivid_start_import" value="Import" />
            <input class="button button-primary" type="button" id="wpvivid_rechoose_import_file" value="Back" />
        </div>
        <div id="wpvivid_import_step3" style="display: none;">
            <div class="postbox wpvivid-import-log" id="wpvivid_import_log" style="margin-top: 10px; margin-bottom: 0;"></div>
        </div>
        <?php
        $chunk_size = min(wp_max_upload_size()-1024, 1048576*2);
        $plupload_init = array(
            'runtimes'            => 'html5,silverlight,flash,html4',
            'browse_button'       => 'wpvivid_select_import_file_button',
            'file_data_name'      => 'async-upload',
            'max_retries'		    => 3,
            'multiple_queues'     => true,
            'max_file_size'       => '10Gb',
            'chunk_size'        => $chunk_size.'b',
            'url'                 => admin_url('admin-ajax.php'),
            'flash_swf_url'       => includes_url('js/plupload/plupload.flash.swf'),
            'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
            'multipart'           => true,
            'urlstream_upload'    => true,
            'multi_selection'      => false,
            // additional post data to send to our ajax hook
            'multipart_params'    => array(
                '_ajax_nonce' => wp_create_nonce('wpvivid_ajax'),
                'action'      => 'wpvivid_upload_import_files',            // the ajax action name
            ),
        );
        if (is_file(ABSPATH.WPINC.'/js/plupload/Moxie.swf')) {
            $plupload_init['flash_swf_url'] = includes_url('js/plupload/Moxie.swf');
        } else {
            $plupload_init['flash_swf_url'] = includes_url('js/plupload/plupload.flash.swf');
        }

        if (is_file(ABSPATH.WPINC.'/js/plupload/Moxie.xap')) {
            $plupload_init['silverlight_xap_url'] = includes_url('js/plupload/Moxie.xap');
        } else {
            $plupload_init['silverlight_xap_url'] = includes_url('js/plupload/plupload.silverlight.swf');
        }

        // we should probably not apply this filter, plugins may expect wp's media uploader...
        $plupload_init = apply_filters('plupload_init', $plupload_init);
        $upload_file_image = includes_url( '/images/media/archive.png' );
        ?>
        <script type="text/javascript">
            var uploader;
            var import_file_name='';
            jQuery(document).ready(function($)
            {
                // create the uploader and pass the config from above
                jQuery('#wpvivid_upload_submit_btn').hide();
                uploader = new plupload.Uploader(<?php echo json_encode($plupload_init); ?>);

                // checks if browser supports drag and drop upload, makes some css adjustments if necessary
                uploader.bind('Init', function(up)
                {
                    var uploaddiv = $('#wpvivid_plupload-upload-ui');

                    if(up.features.dragdrop){
                        uploaddiv.addClass('drag-drop');
                        $('#drag-drop-area')
                            .bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
                            .bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

                    }else{
                        uploaddiv.removeClass('drag-drop');
                        $('#drag-drop-area').unbind('.wp-uploader');
                    }
                });
                uploader.init();

                function wpvivid_check_plupload_added_files(up, files)
                {
                    jQuery('#wpvivid_import_list').hide();
                    var file=files[0];

                    var ajax_data = {
                        'action': 'wpvivid_check_import_file',
                        'file_name':file.name
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success")
                        {
                            jQuery('#wpvivid_select_import_file_button').css({'pointer-events': 'none', 'opacity': '0.4'});
                            var repeat_files = '';
                            plupload.each(files, function(file)
                            {
                                var brepeat=false;
                                var file_list = jQuery('#wpvivid_upload_file_list span');
                                file_list.each(function (index, value) {
                                    if (value.innerHTML === file.name) {
                                        brepeat=true;
                                    }
                                });
                                if(!brepeat) {
                                    jQuery('#wpvivid_upload_file_list').append(
                                        '<div id="' + file.id + '" style="width: 100%; height: 36px; background: #f1f1f1; margin-bottom: 1px;">' +
                                        '<img src=" <?php echo $upload_file_image; ?> " alt="" style="float: left; margin: 2px 10px 0 3px; max-width: 40px; max-height: 32px;">' +
                                        '<div style="line-height: 36px; float: left; margin-left: 5px;"><span>' + file.name + '</span></div>' +
                                        '<div class="fileprogress" style="line-height: 36px; float: right; margin-right: 5px;"></div>' +
                                        '</div>' +
                                        '<div style="clear: both;"></div>'
                                    );
                                    jQuery('#wpvivid_upload_file_list').show();

                                    uploader.refresh();
                                    uploader.start();
                                }
                                else{
                                    if(repeat_files === ''){
                                        repeat_files += file.name;
                                    }
                                    else{
                                        repeat_files += ', ' + file.name;
                                    }
                                }
                            });
                            if(repeat_files !== ''){
                                alert(repeat_files + " already exists in upload list.");
                                repeat_files = '';
                            }
                        }
                        else if(jsonarray.result === "failed")
                        {
                            uploader.removeFile(file);
                            alert(jsonarray.error);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('uploading backups', textStatus, errorThrown);
                        uploader.removeFile(file);
                        alert(error_message);
                    });
                }

                uploader.bind('FilesAdded', wpvivid_check_plupload_added_files);

                uploader.bind('Error', function(up, error)
                {
                    alert('Upload ' + error.file.name +' error, error code: ' + error.code + ', ' + error.message);
                    console.log(error);
                });

                uploader.bind('FileUploaded', function(up, file, response)
                {
                    var jsonarray = jQuery.parseJSON(response.response);
                    if(jsonarray.result == 'failed'){
                        alert('upload ' + file.name + ' failed, ' + jsonarray.error);
                    }
                });

                uploader.bind('UploadProgress', function(up, file)
                {
                    jQuery('#' + file.id + " .fileprogress").html(file.percent + "%");
                });

                uploader.bind('UploadComplete',function(up, files)
                {
                    jQuery('#wpvivid_select_import_file_button').css({'pointer-events': 'auto', 'opacity': '1'});
                    var ajax_data = {
                        'action': 'wpvivid_upload_import_file_complete',
                        'files':JSON.stringify(files)
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if(jsonarray.result === 'success')
                            {
                                jQuery('#wpvivid_upload_file_list').html("");
                                jQuery('#wpvivid_upload_file_list').hide();
                                wpvivid_import_step2(jsonarray.data);
                            }
                            else if(jsonarray.result === 'failed')
                            {
                                jQuery('#wpvivid_upload_file_list').html("");
                                jQuery('#wpvivid_upload_file_list').hide();
                                alert(jsonarray.error);
                            }
                        }
                        catch(err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('refreshing backup list', textStatus, errorThrown);
                        alert(error_message);
                    });
                    plupload.each(files, function(file)
                    {
                        if(typeof file === 'undefined')
                        {

                        }
                        else
                        {
                            uploader.removeFile(file.id);
                        }
                    });
                })
            });

            function wpvivid_clean_import_folder()
            {
                var descript = 'Are you sure you want to delete all the exported files in the /ImportandExport folder? All the export files in the folder will be permanently deleted.';
                var ret = confirm(descript);
                if(ret === true){
                    var ajax_data = {
                        'action': 'wpvivid_clean_import_folder'
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if(jsonarray.html !== false) {
                                jQuery('#wpvivid_import_list').html(jsonarray.html);
                                jQuery('#wpvivid_empty_import_folder').val('Delete Exported Files In Folder ('+jsonarray.size+')');
                            }
                        }
                        catch(err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = wpvivid_output_ajaxerror('scanning import folder', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            function wpvivid_import_step2(data)
            {
                jQuery('#wpvivid_import_file_data').html('');
                var import_type = 'pages';
                jQuery.each(data, function (index, value)
                {
                    import_type = value['export_type'];
                    import_file_name=value['file_name'];
                    var list = "";
                    var myDate = new Date(value['time']*1000);
                    list += "<li>File name: " + value['file_name'] + "</li>";
                    list += "<li>Post type: " + value['export_type'] + "</li>";
                    list += "<li>Posts: " + value['posts_count'] + "</li>";
                    list += "<li>Media files size: " + value['media_size'] + "</li>";
                    list += "<li>Export time: " + myDate.toLocaleString('en-us') + "</li>";
                    jQuery("#wpvivid_import_file_data").append("<ul>"+ list +"</ul>");
                });

                jQuery('#wpvivid_import_type').html('Overwrite existing '+import_type+'s');
                jQuery('#wpvivid_import_step1').hide();
                jQuery('#wpvivid_import_step2').show();
                jQuery('#wpvivid_import_step3').hide();
            }

            function wpvivid_import_step3()
            {
                jQuery('#wpvivid_import_step1').hide();
                jQuery('#wpvivid_import_step2').hide();
                jQuery('#wpvivid_import_step3').show();
            }

            function wpvivid_return_import_page(){
                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-export-import', 'wpvivid-export-import').'&import_page'; ?>';
            }

            function wpvivid_monitor_import_task()
            {
                var ajax_data = {
                    'action': 'wpvivid_get_import_progress',
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (typeof jsonarray === 'object')
                        {
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#wpvivid_import_log').html("");
                                while (jsonarray.log.indexOf('\n') >= 0)
                                {
                                    var iLength = jsonarray.log.indexOf('\n');
                                    var log = jsonarray.log.substring(0, iLength);
                                    jsonarray.log = jsonarray.log.substring(iLength + 1);
                                    var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                                    jQuery('#wpvivid_import_log').append(insert_log);
                                    var div = jQuery('#wpvivid_import_log');
                                    div[0].scrollTop = div[0].scrollHeight;
                                }
                                if (jsonarray.status === 'wait')
                                {
                                    setTimeout(function () {
                                        wpvivid_monitor_import_task();
                                    }, 1000);
                                }
                                else if (jsonarray.status === 'completed')
                                {
                                    var insert_log = "<div style=\"clear:both;\"><a style='cursor: pointer;' onclick='wpvivid_return_import_page();'>Return to import page</a></div>";
                                    jQuery('#wpvivid_import_log').append(insert_log);
                                    var div = jQuery('#wpvivid_import_log');
                                    div[0].scrollTop = div[0].scrollHeight;
                                    setTimeout(function () {
                                        alert("Import completed successfully.");
                                    }, 1000);
                                    wpvivid_import_lock_unlock('unlock');
                                }
                                else if (jsonarray.status === 'error')
                                {
                                    alert("Import failed.");
                                    wpvivid_import_lock_unlock('unlock');
                                }
                                else
                                {
                                    setTimeout(function ()
                                    {
                                        wpvivid_monitor_import_task();
                                    }, 1000);
                                }
                            }
                            else {
                                setTimeout(function () {
                                    wpvivid_monitor_import_task();
                                }, 1000);
                            }
                        }
                        else{
                            setTimeout(function () {
                                wpvivid_monitor_import_task();
                            }, 1000);
                        }
                    }
                    catch (err) {
                        setTimeout(function () {
                            wpvivid_monitor_import_task();
                        }, 1000);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        wpvivid_monitor_import_task();
                    }, 1000);
                });
            }

            jQuery('#wpvivid_start_import').click(function()
            {
                if(import_file_name!=='')
                {
                    var descript = '';
                    var user=jQuery('select[name="user_map"]').val();
                    if(user !== '0'){
                        wpvivid_start_import(import_file_name, user);
                    }
                    else{
                        alert('Please select an existing author to start importing.');
                    }
                }
            });

            jQuery('#wpvivid_rechoose_import_file').click(function(){
                jQuery('#wpvivid_import_step1').show();
                jQuery('#wpvivid_import_step2').hide();
                jQuery('#wpvivid_import_step3').hide();
            });

            function wpvivid_start_import (file_name, user)
            {
                if(jQuery('#wpvivid_overwrite_existing').prop('checked')){
                    var overwrite_existing = 1;
                }
                else{
                    var overwrite_existing = 0;
                }

                wpvivid_import_lock_unlock('lock');
                wpvivid_monitor_import_task();
                wpvivid_import_step3();
                var ajax_data = {
                    'action':'wpvivid_start_import',
                    'file_name':file_name,
                    'user':user,
                    'update_exist':overwrite_existing
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            var wpvivid_scan_data={};

            function wpvivid_refresh_import_list()
            {
                var ajax_data = {
                    'action': 'wpvivid_scan_import_folder'
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.html !== false)
                        {
                            wpvivid_scan_data=jsonarray.data;
                            jQuery('#wpvivid_import_list').show();
                            jQuery('#wpvivid_import_list').html(jsonarray.html);
                        }
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('scanning import folder', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_import_list').on("click",'.first-page',function()
            {
                wpvivid_change_import_page('first');
            });

            jQuery('#wpvivid_import_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_change_import_page(page-1);
            });

            jQuery('#wpvivid_import_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_change_import_page(page+1);
            });

            jQuery('#wpvivid_import_list').on("click",'.last-page',function()
            {
                wpvivid_change_import_page('last');
            });

            jQuery('#wpvivid_import_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_change_import_page(page);
                }
            });

            function wpvivid_change_import_page(page)
            {
                var post_ids = {};

                jQuery('input[name="export[]"]').each(function (i)
                {
                    var id=jQuery(this).val();
                    if(jQuery(this).prop('checked'))
                    {
                        post_ids[id]=1;
                    }
                    else
                    {
                        post_ids[id]=0;
                    }
                });

                var ajax_data = {
                    'action':'wpvivid_get_import_list_page',
                    'page': page,
                    'post_ids':post_ids
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_import_list').html(jsonarray.rows);
                    }
                    else if (jsonarray.result === 'failed')
                    {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_import_list').on("click",".wpvivid-export-list-item td",function()
            {
                var id = jQuery(this).parent().attr('id');

                if(jQuery(this).find('div.export-list-import').length !== 0)
                {
                    var data={};
                    data[id]=wpvivid_scan_data[id];
                    console.log(data[id]);
                    wpvivid_import_step2(data);
                    jQuery('#wpvivid_import_list').hide();
                }
            });

            function wpvivid_calc_import_folder_size(){
                var ajax_data = {
                    'action': 'wpvivid_calc_import_folder_size'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_empty_import_folder').val('Delete Imported Files in the Folder ('+jsonarray.size+')');
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('calc import folder size', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery(document).ready(function (){
                wpvivid_calc_import_folder_size();
            });
        </script>
        <?php
    }

    public function output_url_replace_page()
    {
        update_option('wpvivid_search_replace_task',array(),'no');

        $replace_max_execution_time = 900;
        $database_tables = self::get_database_tables();

        ?>
        <div style="margin-top: 10px;">
            <div class="postbox schedule-tab-block wpvivid-setting-addon" style="padding-bottom: 0; margin-bottom: 0;">
                <div class="wpvivid-element-space-bottom">
                    <?php _e( 'This tool allows you to do a quick domain/url replacing in the database, with no need to perform a database migration.', 'wpvivid' ); ?>
                </div>
                <div class="wpvivid-element-space-bottom"><strong>Note:</strong></div>
                <div class="wpvivid-element-space-bottom"><span>1. It’s strongly recommend to take a full database backup before a replacement.</span></div>
                <div class="wpvivid-element-space-bottom"><span>2. Make sure that the formats of the old and new url are consistent. For example. old: https://test1.com, new: https://test2.com, old: http://test1.com/wordpress/, new: http://test2.com/wordpress/.</span></div>
                <div class="wpvivid-element-space-bottom"><span>3. This tool supports serialized data.</span></div>

                <div id="wpvivid_replace_step_1">
                    <div class="wpvivid-element-space-bottom"><strong><?php _e('Search for:', 'wpvivid'); ?></strong></div>
                    <div class="wpvivid-element-space-bottom">
                        <input type="text" class="all-options" option="search_and_replace" name="search_for" placeholder="http://source.domain.com" value="" />
                    </div>

                    <div class="wpvivid-element-space-bottom"><strong><?php _e('Replace with:', 'wpvivid'); ?></strong></div>
                    <div class="wpvivid-element-space-bottom">
                        <input type="text" class="all-options" option="search_and_replace" name="replace_with" placeholder="http://target.domain.com" value="" />
                    </div>

                    <div class="wpvivid-element-space-bottom"><strong><?php _e('Replace execution time:', 'wpvivid'); ?></strong></div>
                    <div class="wpvivid-element-space-bottom">
                        <input type="text" class="all-options" option="search_and_replace" name="replace_execution_time" placeholder="900" value="<?php esc_attr_e($replace_max_execution_time); ?>" onkeyup="value=value.replace(/\D/g,'')" />
                    </div>

                    <div class="wpvivid-element-space-bottom"><strong><?php _e('Select table you want to search', 'wpvivid'); ?></strong></div>
                    <div style="width:100%; border:1px solid #f1f1f1; float:left; box-sizing: border-box;margin-bottom:10px;">
                        <div id="wpvivid_database_table_tool" style="padding: 10px;">
                            <?php echo $database_tables; ?>
                        </div>
                    </div>

                    <div class="wpvivid-element-space-bottom">
                        <input class="button-primary" id="wpvivid_search_and_replace" type="submit" value="<?php esc_attr_e( 'Search & Replace Now', 'wpvivid' ); ?>" />
                    </div>
                </div>

                <div id="wpvivid_replace_step_2" style="display: none;">
                    <div class="postbox wpvivid-staging-log wpvivid-element-space-bottom" id="wpvivid_replace_log"></div>
                    <div class="wpvivid-element-space-bottom">
                        <input class="button-primary" id="wpvivid_back_step_1" type="submit" value="<?php esc_attr_e( 'Back', 'wpvivid' ); ?>" />
                    </div>
                </div>
            </div>
        </div>
        <script>
            var wpvivid_replace_timeout = false;
            var wpvivid_replace_exection_time='<?php echo $replace_max_execution_time; ?>';
            wpvivid_replace_exection_time=wpvivid_replace_exection_time*1000;

            jQuery('.wpvivid-tool-base-table-check').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    jQuery('input[option=tool_base_db][name=tool_Database]').prop('checked', true);
                }
                else
                {
                    jQuery('input[option=tool_base_db][name=tool_Database]').prop('checked', false);
                }
            });

            jQuery('.wpvivid-tool-other-table-check').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    jQuery('input[option=tool_other_db][name=tool_Database]').prop('checked', true);
                }
                else
                {
                    jQuery('input[option=tool_other_db][name=tool_Database]').prop('checked', false);
                }
            });

            jQuery('input[option=tool_base_db][name=tool_Database]').click(function()
            {
                var all_check = true;
                jQuery('input[option=tool_base_db][name=tool_Database]').each(function()
                {
                    if(!jQuery(this).prop('checked')){
                        all_check = false;
                    }
                });
                if(all_check){
                    jQuery('.wpvivid-tool-base-table-check').prop('checked', true);
                }
                else{
                    jQuery('.wpvivid-tool-base-table-check').prop('checked', false);
                }
            });

            jQuery('input[option=tool_other_db][name=tool_Database]').click(function()
            {
                var all_check = true;
                jQuery('input[option=tool_other_db][name=tool_Database]').each(function()
                {
                    if(!jQuery(this).prop('checked')){
                        all_check = false;
                    }
                });
                if(all_check){
                    jQuery('.wpvivid-tool-other-table-check').prop('checked', true);
                }
                else{
                    jQuery('.wpvivid-tool-other-table-check').prop('checked', false);
                }
            });

            jQuery('#wpvivid_search_and_replace').click(function()
            {
                var check_res = wpvivid_check_replace_options();
                if(check_res) {
                    wpvivid_replace_timeout = false;
                    var wpvivid_replace_exection_time = jQuery('input[option=search_and_replace][name=replace_execution_time]').val();
                    var option_data = wpvivid_ajax_data_transfer('search_and_replace');
                    var tables = Array();
                    jQuery('input[name=tool_Database][type=checkbox]').each(function (index, value) {
                        if (jQuery(value).prop('checked')) {
                            tables.push(jQuery(value).val());
                        }
                    });
                    var ajax_data = {
                        'action': 'wpvivid_search_and_replace',
                        'options': option_data,
                        'tables': tables
                    };

                    jQuery('#wpvivid_search_and_replace').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_replace_step_1').hide();
                    jQuery('#wpvivid_replace_step_2').show();
                    jQuery('#wpvivid_replace_log').html("");
                    wpvivid_get_replace_progress();
                    setTimeout(function () {
                        wpvivid_replace_timeout = true;
                    }, wpvivid_replace_exection_time * 1000);

                    wpvivid_post_request_addon(ajax_data, function (data) {
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                    });
                }
            });

            jQuery('#wpvivid_back_step_1').click(function()
            {
                jQuery('#wpvivid_replace_step_1').show();
                jQuery('#wpvivid_replace_step_2').hide();
            });

            function wpvivid_get_replace_progress()
            {
                var ajax_data = {
                    'action': 'wpvivid_get_replace_progress'
                };

                if(wpvivid_replace_timeout)
                {
                    wpvivid_replace_timeout = false;
                    var replace_msg = "<div style=\"clear:both;\">Database replace times out.</div>";
                    jQuery('#wpvivid_replace_log').append(replace_msg);
                    jQuery('#wpvivid_search_and_replace').css({'pointer-events': 'auto', 'opacity': '1'});
                }
                else
                {
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (typeof jsonarray === 'object')
                            {
                                if (jsonarray.result === "success")
                                {
                                    wpvivid_display_replace_log(jsonarray.log);
                                    if (jsonarray.status === 'wait')
                                    {
                                        setTimeout(function () {
                                            wpvivid_get_replace_progress();
                                        }, 3000);
                                    }
                                    else if (jsonarray.status === 'start')
                                    {
                                        setTimeout(function () {
                                            wpvivid_get_replace_progress();
                                        }, 3000);
                                    }
                                    else if (jsonarray.status === 'finish')
                                    {
                                        jQuery('#wpvivid_search_and_replace').css({'pointer-events': 'auto', 'opacity': '1'});
                                        alert("Replace completed successfully.");
                                    }
                                    else if (jsonarray.status === 'error')
                                    {
                                        jQuery('#wpvivid_search_and_replace').css({'pointer-events': 'auto', 'opacity': '1'});
                                        alert("Replace failed.");
                                    }
                                    else {
                                        setTimeout(function ()
                                        {
                                            wpvivid_get_replace_progress();
                                        }, 3000);
                                    }
                                }
                                else {
                                    setTimeout(function () {
                                        wpvivid_get_replace_progress();
                                    }, 3000);
                                }
                            }
                            else
                            {
                                setTimeout(function ()
                                {
                                    wpvivid_get_replace_progress();
                                }, 3000);
                            }
                        }
                        catch (err)
                        {
                            setTimeout(function ()
                            {
                                wpvivid_get_replace_progress();
                            }, 3000);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        setTimeout(function () {
                            wpvivid_get_replace_progress();
                        }, 3000);
                    });
                }
            }

            function wpvivid_display_replace_log(data)
            {
                jQuery('#wpvivid_replace_log').html("");
                while (data.indexOf('\n') >= 0)
                {
                    var iLength = data.indexOf('\n');
                    var log = data.substring(0, iLength);
                    data = data.substring(iLength + 1);
                    var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                    jQuery('#wpvivid_replace_log').append(insert_log);
                    var div = jQuery('#wpvivid_replace_log');
                    div[0].scrollTop = div[0].scrollHeight;
                }
            }

            function wpvivid_check_replace_options()
            {
                var search_for = jQuery('input[option=search_and_replace][name=search_for]').val();
                var replace_with = jQuery('input[option=search_and_replace][name=replace_with]').val();
                var replace_execution_time = jQuery('input[option=search_and_replace][name=replace_execution_time]').val();
                if(search_for === ''){
                    alert('Please enter the url you want to search for.');
                    return false;
                }
                if(replace_with === ''){
                    alert('Please enter the url you want to replace with.');
                    return false;
                }
                if(replace_execution_time === ''){
                    alert('Please set a value for replace execution time.');
                    return false;
                }
                var has_base_table = false;
                var has_other_table = false;
                jQuery('input[option=tool_base_db][name=tool_Database]').each(function()
                {
                    if(jQuery(this).prop('checked')){
                        has_base_table = true;
                    }
                });
                jQuery('input[option=tool_other_db][name=tool_Database]').each(function()
                {
                    if(jQuery(this).prop('checked')){
                        has_other_table = true;
                    }
                });
                if(!has_base_table && !has_other_table){
                    alert('Please select the tables you want to search.');
                    return false;
                }
                return true;
            }
        </script>
        <?php
    }

    public function output_export_import_log_list()
    {
        $log_list = new WPvivid_Log_addon();
        $log_list->output_other_log_list();
        ?>
        <script>
            function wpvivid_open_log(log,slug) {
                var ajax_data = {
                    'action':'wpvivid_view_log_ex',
                    'log': log
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('#wpvivid_read_log_content').html("");
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success")
                        {
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'open_log', 'export_import_log_list' ]);
                            var log_data = jsonarray.data;
                            while (log_data.indexOf('\n') >= 0)
                            {
                                var iLength = log_data.indexOf('\n');
                                var log = log_data.substring(0, iLength);
                                log_data = log_data.substring(iLength + 1);
                                var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                                jQuery('#wpvivid_read_log_content').append(insert_log);
                            }
                        }
                        else
                        {
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'open_log', 'export_import_log_list' ]);
                            jQuery('#wpvivid_read_log_content').html(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        var div = "Reading the log failed. Please try again.";
                        jQuery('#wpvivid_read_log_content').html(div);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_download_log(log) {
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_log&log='+log;
            }

            function wpvivid_log_change_page(page,type,log_contenter) {
                var ajax_data = {
                    'action':'wpvivid_get_log_list_page',
                    'page': page,
                    'type':type
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#'+log_contenter).html(jsonarray.rows);
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('changing log page', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function output_open_log()
    {
        ?>
        <div class="postbox restore_log" id="wpvivid_read_log_content" style="word-break: break-all;word-wrap: break-word;"></div>
        <?php
    }
}