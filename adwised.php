<?php

/**
 * Plugin Name: adwised
 * Plugin URI: https://wordpress.org/plugins/adwised
 * Version: 2.5.7
 * Author: Adwised
 * Author URI: https://adwised.com
 * Description: افزودن اسکریپت ادوایزد به سایت
 * License: GPL2
 * Text Domain: adwised
 * Domain Path: languages
 *  Copyright 2022 Adwised
developed by adwised team
 */

/**
 * Insert Headers and Footers Class
 */
class insertAdwisedScript
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Plugin Details
        $this->plugin               = new stdClass;
        $this->plugin->name         = 'adwised'; // Plugin Folder
        $this->plugin->displayName  = 'ادوایزد'; // Plugin Name
        $this->plugin->version      = '2.5.7';
        $this->plugin->folder       = plugin_dir_path(__FILE__);
        $this->plugin->url          = plugin_dir_url(__FILE__);
        $this->plugin->db_welcome_dismissed_key = $this->plugin->name . '_welcome_dismissed_key';


        // Check if the global wpb_feed_append variable exists. If not, set it.
        if (!array_key_exists('wpb_feed_append', $GLOBALS)) {
            $GLOBALS['wpb_feed_append'] = false;
        }

        // Hooks
        add_action('admin_init', array(&$this, 'registerSettings'));
        add_action('admin_menu', array(&$this, 'adminPanelsAndMetaBoxes'));
        add_action('admin_notices', array(&$this, 'dashboardNotices'));
        add_action('wp_ajax_' . $this->plugin->name . '_dismiss_dashboard_notices', array(&$this, 'dismissDashboardNotices'));

        // Frontend Hooks
        function adwised_main_js()
        {
            wp_enqueue_script('mainjs', "https://adwisedfs.com/adwised-webpush.min.js?ver=8.9");
        }

        $adwisedPopupConfig = get_option('adwisedPopConfig');

        $url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $homeurl = home_url();
        if (!get_option('adwisedPopDeactivate')) {
            add_action('wp_footer', array(&$this, 'frontendPopFooter'), 100);
        }


        if (!get_option('adwisedPushDeactivate')) {
            //add_action( 'wp_footer', 'adwised_main_js' ,10);
            add_action('wp_footer', array(&$this, 'frontendFooter'), 15);
        }

        if(!get_option('adwisedIframeDeactivate')){
            add_action('wp_footer', array(&$this, 'frontendIframeFooter'), 15);
        }
        function add_adwised_admin_script_api()
        {
            wp_enqueue_script('adwised_ajaxcall', plugin_dir_url(__FILE__) . "assets/adwised_ajaxcall.js");
        }
        add_action('admin_enqueue_scripts', 'add_adwised_admin_script_api');
    }



    /**
     * Show relevant notices for the plugin
     */
    function dashboardNotices()
    {
        global $pagenow;

        if (!get_option($this->plugin->db_welcome_dismissed_key)) {
            if (!($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'adwised')) {
                $setting_page = admin_url('options-general.php?page=' . $this->plugin->name);
                // load the notices view
                include_once($this->plugin->folder . '/views/dashboard-notices.php');
            }
        }
    }

    /**
     * Dismiss the welcome notice for the plugin
     */
    function dismissDashboardNotices()
    {
        check_ajax_referer($this->plugin->name . '-nonce', 'nonce');
        // user has dismissed the welcome notice
        update_option($this->plugin->db_welcome_dismissed_key, 1);
        exit;
    }

    /**
     * Register Settings
     */
    function registerSettings()
    {
        register_setting($this->plugin->name, 'adwised_topic', 'trim');
        register_setting($this->plugin->name, 'adwised_client_id', 'trim');
        register_setting($this->plugin->name, 'adwised_script', 'trim');
        register_setting($this->plugin->name, 'adwisedPushDeactivate', 'trim');

        register_setting($this->plugin->name, 'adwisedPopId', 'trim');
        register_setting($this->plugin->name, 'adwisedPopSecretkey', 'trim');
        register_setting($this->plugin->name, 'adwisedPopConfig', array(
            'type' => 'string',
        ));
        register_setting($this->plugin->name, 'f', 'trim');

        register_setting($this->plugin->name, 'adwisedPopScript', 'trim');

        //register_setting($this->plugin->name, 'adwisedIframeDeactivate', 'trim');
        //update_option('adwisedIframeDeactivate','1');
        register_setting($this->plugin->name, 'adwisedIframeScript', 'trim');
        register_setting($this->plugin->name, 'adwisedIframeConfig', array(
            'type' => 'string',
        ));
    }

    /**
     * Register the plugin settings panel
     */
    function adminPanelsAndMetaBoxes()
    {
        add_menu_page(
            $this->plugin->displayName, // page title
            'ادوایزد', // menu title
            'manage_options', // user access capability
            $this->plugin->name, // menu slug
            array(&$this, 'adminPanel'), //menu content function
            plugins_url('/adwised/adwisedicon.png', dirname(__FILE__)), // menu icon
            //            'dashicons-ztjalali', // menu icon
            83 // menu position
        );
    }

    /**
     * Output the Administration Panel
     * Save POSTed data from the Administration Panel into a WordPress option
     */
    function adminPanel()
    {
        // only admin user can access this page
        if (!current_user_can('administrator')) {
            echo '<p>' . __('Sorry, you are not allowed to access this page.', 'adwised') . '</p>';
            return;
        }

        // Save Settings
        if (isset($_REQUEST['submit'])) {
            $swFile = dirname(__FILE__);
            $dest = get_home_path();
            copy($swFile . '/firebase-messaging-sw.js', $dest . '/' . 'firebase-messaging-sw.js');

            // Check nonce
            if (!isset($_REQUEST[$this->plugin->name . '_nonce'])) {
                // Missing nonce
                $this->errorMessage = __('nonce field is missing. Settings NOT saved.', 'adwised');
            } elseif (!wp_verify_nonce($_REQUEST[$this->plugin->name . '_nonce'], $this->plugin->name)) {
                // Invalid nonce
                $this->errorMessage = __('Invalid nonce specified. Settings NOT saved.', 'adwised');
            } else {


                // Save
                // $_REQUEST has already been slashed by wp_magic_quotes in wp-settings
                // so do nothing before saving
                update_option('adwised_topic', trim(sanitize_text_field($_REQUEST['adwised_topic'])));
                update_option('adwised_client_id', trim(sanitize_text_field($_REQUEST['adwised_client_id'])));
                update_option('adwised_cpt_select', $_REQUEST['adwised_cpt_select']);
                update_option('adwisedPushDeactivate', $_REQUEST['adwisedPushDeactivate']);

                $pushConfig = new stdClass();
                $pushConfig->client_id = get_option('adwised_client_id');
                $pushConfig->topic = get_option('adwised_topic');
                if (get_option("adwised_welcome_dismissed_key")) {
                    $currentPushConfig = get_option("adwisedPushConfig");
                    $pushConfig->ask_location = $currentPushConfig->ask_location === NULL ? true : $currentPushConfig->ask_location;
                    $pushConfig->double_permission_active = $currentPushConfig->double_permission_active === NULL ? false : $currentPushConfig->double_permission_active;
                } else {
                    $pushConfig->ask_location = true;
                    $pushConfig->double_permission_active = false;
                }
                update_option("adwisedPushConfig", $pushConfig);
                // iframe 
                update_option('adwisedIframeDeactivate',$_REQUEST['adwisedIframeDeactivate']);
                
                // popup
                update_option('adwisedPopDeactivate', $_REQUEST['adwisedPopDeactivate']);
                update_option('adwisedPopId', trim(sanitize_text_field($_REQUEST['adwisedPopId'])));
                update_option('adwisedPopSecretkey', trim(sanitize_text_field($_REQUEST['adwisedPopSecretkey'])));
                setPushScript();
                update_option($this->plugin->db_welcome_dismissed_key, 1);
                $this->message = __('تنظیمات ذخیره شد', 'adwised');

                $endpoint = 'https://popapi.adwised.com/api/plugin/register';

                $body = [
                    'address'  => get_site_url(),
                    'secretKey' => get_option('adwisedPopSecretkey')
                ];

                $body = wp_json_encode($body);

                $options = [
                    'body'        => $body,
                    'headers'     => [
                        'Content-Type' => 'application/json',
                    ],
                    'timeout'     => 60,
                    'redirection' => 5,
                    'blocking'    => true,
                    'httpversion' => '1.0',
                    'sslverify'   => false,
                    'data_format' => 'body',
                ];

                wp_remote_post($endpoint, $options);
            }
        }

        // Get latest settings
        $this->settings = array(
            'adwised_topic' => esc_html(wp_unslash(get_option('adwised_topic'))),
            'adwised_client_id' => esc_html(wp_unslash(get_option('adwised_client_id'))),
        );

        // Load Settings Form
        include_once($this->plugin->folder . '/views/settings.php');
    }

    /**
     * Loads plugin textdomain
     */
    function loadLanguageFiles()
    {
        load_plugin_textdomain('adwised', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    /**
     * Outputs script / CSS to the frontend footer
     */
    function frontendFooter()
    {
        $this->output('adwised_script');
    }
    function frontendPopFooter()
    {
        $this->output('adwisedPopScript');
    }
    function frontendIframeFooter(){
        $this->output('adwisedIframeScript');
    }

    /**
     * Outputs the given setting, if conditions are met
     *
     * @param string $setting Setting Name
     * @return output
     */
    function output($setting)
    {
        // Ignore admin, feed, robots or trackbacks
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return;
        }

        // provide the opportunity to Ignore IHAF - both headers and footers via filters
        if (apply_filters('disable_ihaf', false)) {
            return;
        }

        // provide the opportunity to Ignore IHAF - footer only via filters
        if ('adwised_client_id' == $setting && apply_filters('disable_ihaf_footer', false)) {
            return;
        }

        // provide the opportunity to Ignore IHAF - header only via filters
        if ('adwised_topic' == $setting && apply_filters('disable_ihaf_header', false)) {
            return;
        }

        // Get meta
        $meta = get_option($setting);
        if (empty($meta)) {
            return;
        }
        if (trim($meta) == '') {
            return;
        }

        // Output
        echo wp_unslash($meta);
    }
}
$ihaf = new insertAdwisedScript();


$swFile = dirname(__FILE__);

//get pop url
add_action('wp_ajax_get_adwised_url_first_time', 'get_adwised_url_first_time');
if (!function_exists('get_adwised_url_first_time')) :

    function get_adwised_url_first_time()
    {
        $popId = sanitize_text_field($_POST['popId']);
        $popSecretKey = sanitize_text_field($_POST['popSecretKey']);
        $cURLConnection = curl_init();
        $curlUrl = 'https://popapi.adwised.com/api/home/';
        $curlUrl .= $popId;
        $curlUrl .= '/0';
        curl_setopt($cURLConnection, CURLOPT_URL, $curlUrl);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        $resultStatus = json_decode($server_output, true)["errors"];
        $popConfig = new stdClass();
        $popConfig->pop_url = array($server_output);
        $popConfig->pop_geolocationblock = true;
        $popConfig->pop_count = 1;
        $popConfig->pop_excludequeryselector = "";
        $popConfig->pop_doNotShowOnHomePage = false;

        if ($resultStatus == "") {
            update_option("adwisedPopId", $popId);
            update_option("adwisedPopSecretkey", $popSecretKey);

            update_option("adwisedPopConfig", $popConfig);
            setPopScript();
            print json_encode(array("status" => true, "message" => "پاپ آپ با موفقیت فعال شد"));
        } else {
            print json_encode(array("status" => false, "message" => "خطا در عملیات! لطفا ورودی های خود (پاپ آی دی و سکرت کی) را بررسی کنید و دوباره تلاش کنید (در صورت عدم موفقیت با پشتیبانی ادوایزد تماس بگیرید)"));
        }
        exit();
    }
endif;

add_action('wp_ajax_adwised_get_iframe_link_ajaxcall','adwised_get_iframe_link_ajaxcall');
add_action('wp_ajax_nopriv_adwised_get_iframe_link_ajaxcall','adwised_get_iframe_link_ajaxcall');
function adwised_get_iframe_link_ajaxcall(){
    
    $adwised_iframe_config = get_option('adwisedIframeConfig');
    if($adwised_iframe_config->iframe_geolocationblock == 1){
        $isIranIp = IsIranianIp();
        if($isIranIp){
            print json_encode(array("status" => true, "canShow" => true, "iframeConfig" => $adwised_iframe_config));
        }
        else{
            print json_encode(array("status" => true, "canShow" => false, "iframeConfig" => $adwised_iframe_config));
        }
		exit;
    }
    print json_encode(array("status" => true, "canShow" => false, "iframeConfig" => $adwised_iframe_config));
    exit;
}

add_action('wp_ajax_adwised_get_pop_link_ajaxcall', 'adwised_get_pop_link_ajaxcall');
add_action('wp_ajax_nopriv_adwised_get_pop_link_ajaxcall', 'adwised_get_pop_link_ajaxcall');
function adwised_get_pop_link_ajaxcall()
{
    $isIranIp = IsIranianIp();
    $requestedUrl = $_SERVER["HTTP_REFERER"];
    $homeUrl = home_url();
    $adwisedPopupConfig = get_option('adwisedPopConfig');
    if ($adwisedPopupConfig->pop_doNotShowOnHomePage) {
        if ($requestedUrl == $homeUrl or $requestedUrl == $homeUrl . "/") {
            print json_encode(array("status" => true, "canShow" => "0", "popConfig" => $adwisedPopupConfig));
        } else {
            if ($adwisedPopupConfig->pop_geolocationblock == 1) {
                if ($isIranIp) {
                    print json_encode(array("status" => true, "canShow" => "1", "popConfig" => $adwisedPopupConfig));
                } else {
                    print json_encode(array("status" => true, "canShow" => "0", "popConfig" => $adwisedPopupConfig));
                }
            } else {
                print json_encode(array("status" => true, "canShow" => "1", "popConfig" => $adwisedPopupConfig));
            }
        }
    } else {
        if ($adwisedPopupConfig->pop_geolocationblock == 1) {
            if ($isIranIp) {
                print json_encode(array("status" => true, "canShow" => "1", "popConfig" => $adwisedPopupConfig));
            } else {
                print json_encode(array("status" => true, "canShow" => "0", "popConfig" => $adwisedPopupConfig));
            }
        } else {
            print json_encode(array("status" => true, "canShow" => "1", "popConfig" => $adwisedPopupConfig));
        }
    }

    exit;
}

function auto_update_specific_plugins($update, $item)
{
    // Array of plugin slugs to always auto-update
    $plugins = array('adwised');
    if (in_array($item->slug, $plugins)) {
        // Always update plugins in this array
        return true;
    } else {
        // Else, use the normal API response to decide whether to update or not
        return $update;
    }
}
add_filter('auto_update_plugin', 'auto_update_specific_plugins', 10, 2);


//popup api
add_action('rest_api_init', 'adwisedApi');
function adwisedApi()
{
    register_rest_route('adwised/v1', 'getversion', array(
        'methods' => 'GET',
        'callback' => 'getPluginVersion'
    ));
    register_rest_route('adwised/v1', 'getfunctionality', array(
        'methods' => 'GET',
        'callback' => 'getPluginFunctionality'
    ));
    register_rest_route('adwised/v1', 'setfunctionality', array(
        'methods' => 'POST',
        'callback' => 'setPluginFunctionality'
    ));
    register_rest_route('adwisedpopup/v1', 'getconfig', array(
        'methods' => 'GET',
        'callback' => 'getPopConfig'
    ));
    register_rest_route('adwisedpopup/v1', 'setconfig', array(
        'methods' => 'POST',
        'callback' => 'setPopConfig'
    ));
    register_rest_route('adwisedpush/v1', 'getconfig', array(
        'methods' => 'GET',
        'callback' => 'getPushConfig'
    ));
    register_rest_route('adwisedpush/v1', 'setconfig', array(
        'methods' => 'POST',
        'callback' => 'setPushConfig'
    ));
    register_rest_route('adwisedpush/v1', 'updateScript', array(
        'methods' => 'POST',
        'callback' => 'setUpdateScript'
    ));
    register_rest_route('adwisedIframe/v1', 'getconfig', array(
        'methods' => 'GET',
        'callback' => 'getIframeConfig'
    ));
    register_rest_route('adwisedIframe/v1', 'setconfig', array(
        'methods' => 'POST',
        'callback' => 'setIframeConfig'
    ));
};

function getPluginVersion()
{
    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $plugin_data = get_plugin_data(__FILE__, 'false', 'false');
    return $plugin_data["Version"];
}

function getPluginFunctionality()
{
    $functionality = new stdClass;

    if (!get_option("adwisedPopDeactivate")) {
        $functionality->popEnabled = true;
    } else {
        $functionality->popEnabled = false;
    }
    if (!get_option("adwisedPushDeactivate")) {
        $functionality->pushEnabled = true;
    } else {
        $functionality->pushEnabled = false;
    }
    if (!get_option("adwisedIframeDeactivate")) {
        $functionality->iframeEnabled = true;
    } else {
        $functionality->iframeEnabled = false;
    }
    return $functionality;
}

function setPluginFunctionality()
{
    $data = json_decode(file_get_contents("php://input"));
    if ($data->adwisedSecretKey === get_option('adwisedPopSecretkey')) {
        update_option('adwisedPopDeactivate', !$data->popEnabled);
        update_option('adwisedPushDeactivate', !$data->pushEnabled);
        update_option('adwisedIframeDeactivate',!$data->iframeEnabled);
        return "{status:success}";
    } else {
        return "{status:error,error:wrong secret key}";
    }
}

function getPopConfig()
{
    return get_option("adwisedPopConfig");
};

function setPopConfig()
{
    $data = json_decode(file_get_contents("php://input"));
    if ($data->adwisedSecretKey === get_option('adwisedPopSecretkey')) {
        $popConfig = new stdClass();
        $popConfig->pop_url = $data->adwisedPopUrl;
        $popConfig->pop_geolocationblock = $data->adwisedBlockGeoLocational;
        $popConfig->pop_count = $data->adwisedPopCount;
        $popConfig->pop_excludequeryselector = $data->adwisedExcludeQuerySelector;
        $popConfig->pop_doNotShowOnHomePage = $data->adwisedDoNotShowOnHomePage;

        update_option("adwisedPopConfig", $popConfig);
        setPopScript();

        return "{status:success}";
    } else {
        return "{status:error,error:wrong secret key}";
    }
};
function getIframeConfig(){
    return get_option('adwisedIframeConfig');
}
function setIframeConfig(){
    $data = json_decode(file_get_contents("php://input"));
    if ($data->adwisedSecretKey === get_option('adwisedPopSecretkey')) {
        $IframeConfig = new stdClass();
        $IframeConfig->iframe_url = $data->adwisedIframeUrl;
        $IframeConfig->iframe_doNotShowOnHomePage = $data->adwisedDoNotShowOnHomePage;
        $IframeConfig->iframe_geolocationblock = $data->adwisedBlockGeoLocational;
        update_option("adwisedIframeConfig", $IframeConfig);
        setIframeScript();

        return "{status:success}";
    } else {
        return "{status:error,error:wrong secret key}";
    }
}
function getPushConfig()
{
    return get_option("adwisedPushConfig");
};

function setPushConfig()
{
    $data = json_decode(file_get_contents("php://input"));

    if ($data->adwisedSecretKey === get_option('adwisedPopSecretkey')) {
        $pushConfig = new stdClass();
        $pushConfig->client_id = $data->adwisedClientId;
        $pushConfig->topic = $data->adwisedTopic;
        $pushConfig->ask_location = $data->adwisedAskLocation;
        $pushConfig->double_permission_active = $data->adwisedDoublePermissionActive;
        $pushConfig->double_permission_isNativeVersion = $data->adwisedDoublePermissionIsNativeVersion;

        update_option("adwised_client_id", $data->adwisedClientId);
        update_option("adwised_topic", $data->adwisedTopic);
        update_option("adwisedPushConfig", $pushConfig);
        setPushScript();

        return "{status:success}";
    } else {
        return "{status:error,error:wrong secret key}";
    }
}


function setPushScript()
{
    if ( ! function_exists( 'get_home_path' ) ) {
        include_once ABSPATH . '/wp-admin/includes/file.php';
    }
	$swFile = dirname(__FILE__);
	$dest = get_home_path();
	copy($swFile . '/firebase-messaging-sw.js', $dest . '/' . 'firebase-messaging-sw.js');
	
    $pushConfig = get_option('adwisedPushConfig');
    $modalHtml = '
        <div id="adwised" class="animate__animated animate__fadeIn"><div class="adwised-inner animate__animated"><div class="adwised-main"><div class="adwised-content"><div class="adwised-title"></div><div class="adwised-body"><button class="adwised-confirm"></button><button class="adwised-cancel"></button></div></div><div class="adwised-logo"><img src="{{iconUrl}}" alt="" class="adwised-logo-img"></div></div></div></div>
    ';
    
    $ids = explode("_CID_", $pushConfig->client_id);

$adwised_scrip = '    
<!-- Adw_Plgn -->
<script>            
            adwisedScriptTag = document.createElement("script");
            var now = new Date();             
            adwisedScriptTag = Object.assign(adwisedScriptTag,            
            {            
            type: "text/javascript",            
            async: true,            
            src: "https://scriptapi.adwisedfs.com/api/webpush/' . $ids[1] . '.js?site="+ location.host + "&ver=" + now.getFullYear() + now.getMonth() + now.getDate() + now.getHours()            
            });            
            document.head.appendChild(adwisedScriptTag);            
</script>    
';



    if($pushConfig->double_permission_active){
        $adwised_scrip = $modalHtml . $adwised_scrip;
    }
    update_option('adwised_script', $adwised_scrip);
}
function setPopScript(){
	$adwised_pop_scrip = '<script>
    var ajaxurl = \"' . admin_url("admin-ajax.php") . '\"; 
    fetch(ajaxurl+"?action=adwised_get_pop_link_ajaxcall")
    .then((resp) => resp.json())
    .then(function(data) {
            document.adwisedCanShow=data.canShow;
            document.adwisedPopLinks=data.popConfig.pop_url;
            document.adwisedPopCount = data.popConfig.pop_count;
            document.adwisedExcludeQuerySelector =data.popConfig.pop_excludequeryselector;
            document.adwisedPopId="' . get_option("adwisedPopId") . '";
            document.adwisedDynamic =true;
            if(document.adwisedPopLinks){
            adwisedPop();
            }
        });
    </script>
    <script src=\"' . plugin_dir_url(__FILE__) . "assets/adwisedpop.js" . '\"></script>
    ';
	update_option('adwisedPopScript', $adwised_pop_scrip);
}
function setIframeScript(){

    $adwised_iframe_script = '
    <div id="adwisedIframeDiv"></div>
    <script>
    var ajaxurl = \"'. admin_url("admin-ajax.php"). '\";
    fetch(ajaxurl+"?action=adwised_get_iframe_link_ajaxcall").
    then((resp) => resp.json())
    .then(function(data) {
        document.adwisedIframeCanShow= data.canShow;
        document.adwisedIframeLink = data.iframeConfig.iframe_url;
        if(data.canShow && adwisedIframe()){
            var z = document.createElement("iframe");
            z.src = "'.get_option("adwisedIframeConfig")->iframe_url.'";
            z.name = "iframe_adwised";
            z.width = "1px";
            z.height = "1px";
            z.scrolling = "No";
            z.frameborder = "0";
            document.body.appendChild(z);
        }
    });
    </script>
    <script src=\"' . plugin_dir_url(__FILE__) . "assets/adwisedpop.js" . '\"></script>
    ';
    update_option('adwisedIframeScript',$adwised_iframe_script);    
}
// get ip
function adwisedGetUserIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
//ip check function
function adwised_ip_in_range($ip, $range)
{
    if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
        list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
            // $netmask is a 255.255.0.0 format
            $netmask = str_replace('*', '0', $netmask);
            $netmask_dec = ip2long($netmask);
            return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
        } else {
            // $netmask is a CIDR size block
            // fix the range argument
            $x = explode('.', $range);
            while (count($x) < 4) $x[] = '0';
            list($a, $b, $c, $d) = $x;
            $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
            $range_dec = ip2long($range);
            $ip_dec = ip2long($ip);

            # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
            #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

            # Strategy 2 - Use math to create it
            $wildcard_dec = pow(2, (32 - $netmask)) - 1;
            $netmask_dec = ~$wildcard_dec;

            return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
    } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
        if (strpos($range, '*') !== false) { // a.b.*.* format
            // Just convert to A-B format by setting * to 0 for A and 255 for B
            $lower = str_replace('*', '0', $range);
            $upper = str_replace('*', '255', $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, '-') !== false) { // A-B format
            list($lower, $upper) = explode('-', $range, 2);
            $lower_dec = (float)sprintf("%u", ip2long($lower));
            $upper_dec = (float)sprintf("%u", ip2long($upper));
            $ip_dec = (float)sprintf("%u", ip2long($ip));
            return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
        }
        echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';
        return false;
    }
}
add_action( 'upgrader_process_complete', function( $upgrader_object, $options ) {
    // inspect $options
	setPushScript();
	setPopScript();
    setIframeScript();
}, 10, 2 );

function setUpdateScript(){
    $data = json_decode(file_get_contents("php://input"));
    if ($data->adwisedSecretKey === get_option('adwisedPopSecretkey')) {
		
		
        setPushScript();
	    setPopScript();
        setIframeScript();
        return "{status:success}";
    } else {
        return "{status:error,error:wrong secret key}";
    }

}

function IsIranianIp(){
    $ipRanges = array(
        "2.144.0.0-2.147.255.255",
        "2.176.0.0-2.191.255.255",
        "5.22.0.0-5.22.127.255",
        "5.22.192.0-5.22.207.255",
        "5.23.112.0-5.23.119.255",
        "5.34.208.0-5.34.223.255",
        "5.52.0.0-5.52.255.255",
        "5.53.32.0-5.53.63.255",
        "5.56.128.0-5.56.132.255",
        "5.56.134.0-5.56.135.255",
        "5.57.32.0-5.57.39.255",
        "5.61.24.0-5.61.26.255",
        "5.61.29.0-5.61.31.255",
        "5.62.160.0-5.62.255.255",
        "5.63.8.0-5.63.15.255",
        "5.72.0.0-5.75.127.255",
        "5.104.208.0-5.104.215.255",
        "5.106.0.0-5.106.255.255",
        "5.112.0.0-5.127.255.255",
        "5.134.128.0-5.134.199.255",
        "5.144.128.0-5.144.135.255",
        "5.145.112.0-5.145.119.255",
        "5.159.48.0-5.159.55.255",
        "5.160.0.0-5.160.139.255",
        "5.160.141.0-5.160.255.255",
        "5.182.44.0-5.182.47.255",
        "5.190.0.0-5.190.255.255",
        "5.198.160.0-5.198.191.255",
        "5.200.64.0-5.200.255.255",
        "5.201.128.0-5.202.255.255",
        "5.208.0.0-5.223.255.255",
        "5.226.50.0-5.226.50.255",
        "5.232.0.0-5.236.151.255",
        "5.236.156.0-5.239.255.255",
        "5.250.0.0-5.250.127.255",
        "5.252.216.0-5.252.219.255",
        "5.253.24.0-5.253.27.255",
        "5.253.96.0-5.253.99.255",
        "31.2.128.0-31.2.255.255",
        "31.7.64.0-31.7.77.255",
        "31.7.88.0-31.7.91.255",
        "31.7.96.0-31.7.143.255",
        "31.14.80.0-31.14.95.255",
        "31.14.112.0-31.14.127.255",
        "31.14.144.0-31.14.159.255",
        "31.24.200.0-31.24.207.255",
        "31.24.232.0-31.24.239.255",
        "31.25.88.0-31.25.89.254",
        "31.25.90.0-31.25.95.255",
        "31.25.104.0-31.25.111.255",
        "31.25.128.0-31.25.135.255",
        "31.25.232.0-31.25.233.255",
        "31.40.0.0-31.40.7.255",
        "31.47.32.0-31.47.63.255",
        "31.56.0.0-31.59.255.255",
        "31.130.176.0-31.130.191.255",
        "31.170.48.0-31.170.63.255",
        "31.171.216.0-31.171.223.255",
        "31.184.128.0-31.184.191.255",
        "31.193.112.0-31.193.119.255",
        "31.214.228.0-31.214.231.255",
        "31.214.248.0-31.214.255.255",
        "31.217.208.0-31.217.215.255",
        "37.9.248.0-37.9.255.255",
        "37.10.64.0-37.10.67.255",
        "37.19.80.0-37.19.95.255",
        "37.32.0.0-37.32.47.255",
        "37.32.112.0-37.32.127.255",
        "37.44.56.0-37.44.63.255",
        "37.49.144.0-37.49.151.255",
        "37.63.128.0-37.63.255.255",
        "37.75.240.0-37.75.247.255",
        "37.98.0.0-37.98.127.255",
        "37.114.192.0-37.114.255.255",
        "37.128.240.0-37.129.255.255",
        "37.130.200.0-37.130.207.255",
        "37.137.0.0-37.137.255.255",
        "37.143.144.0-37.143.151.255",
        "37.148.0.0-37.148.127.255",
        "37.148.248.0-37.148.251.255",
        "37.152.160.0-37.152.191.255",
        "37.153.128.0-37.153.131.255",
        "37.153.176.0-37.153.191.255",
        "37.156.0.0-37.156.3.255",
        "37.156.8.0-37.156.31.255",
        "37.156.48.0-37.156.63.255",
        "37.156.100.0-37.156.103.255",
        "37.156.112.0-37.156.147.255",
        "37.156.152.0-37.156.167.255",
        "37.156.176.0-37.156.179.255",
        "37.156.212.0-37.156.215.255",
        "37.156.232.0-37.156.243.255",
        "37.156.248.0-37.156.251.255",
        "37.191.64.0-37.191.95.255",
        "37.202.128.0-37.202.255.255",
        "37.221.0.0-37.221.63.255",
        "37.228.131.0-37.228.131.255",
        "37.228.133.0-37.228.133.255",
        "37.228.135.0-37.228.139.255",
        "37.235.16.0-37.235.31.255",
        "37.254.0.0-37.255.128.111",
        "37.255.128.128-37.255.186.255",
        "37.255.187.32-37.255.255.255",
        "45.8.160.0-45.8.163.255",
        "45.9.144.0-45.9.147.255",
        "45.9.252.0-45.9.255.255",
        "45.15.200.0-45.15.203.255",
        "45.15.248.0-45.15.251.255",
        "45.65.112.0-45.65.115.255",
        "45.81.16.0-45.81.19.255",
        "45.82.136.0-45.82.139.255",
        "45.84.156.0-45.84.159.255",
        "45.84.248.0-45.84.251.255",
        "45.86.4.0-45.86.7.255",
        "45.86.196.0-45.86.199.255",
        "45.87.4.0-45.87.7.255",
        "45.89.136.0-45.89.139.255",
        "45.89.200.0-45.89.203.255",
        "45.89.236.0-45.89.239.255",
        "45.90.72.0-45.90.75.255",
        "45.91.152.0-45.91.155.255",
        "45.92.92.0-45.92.95.255",
        "45.93.168.0-45.93.171.255",
        "45.94.212.0-45.94.215.255",
        "45.94.252.0-45.94.255.255",
        "45.128.140.0-45.128.143.255",
        "45.129.36.0-45.129.39.255",
        "45.129.116.0-45.129.119.255",
        "45.132.32.0-45.132.32.255",
        "45.132.168.0-45.132.175.255",
        "45.135.240.0-45.135.243.255",
        "45.138.132.0-45.138.135.255",
        "45.139.8.0-45.139.11.255",
        "45.139.100.0-45.139.103.255",
        "45.140.224.0-45.140.231.255",
        "45.142.188.0-45.142.191.255",
        "45.144.16.0-45.144.19.255",
        "45.144.124.0-45.144.127.255",
        "45.146.240.0-45.146.243.255",
        "45.147.76.0-45.147.79.255",
        "45.148.248.0-45.148.251.255",
        "45.149.76.0-45.149.79.255",
        "45.150.88.0-45.150.91.255",
        "45.155.192.0-45.155.195.255",
        "45.156.180.0-45.156.187.255",
        "45.156.192.0-45.156.203.255",
        "45.157.100.0-45.157.103.255",
        "45.157.244.0-45.157.247.255",
        "45.158.120.0-45.158.123.255",
        "45.159.112.0-45.159.115.255",
        "45.159.148.0-45.159.151.255",
        "45.159.196.0-45.159.199.255",
        "45.199.159.0-45.199.159.255",
        "46.18.248.0-46.18.255.255",
        "46.21.80.0-46.21.95.255",
        "46.28.72.0-46.28.79.255",
        "46.32.0.0-46.32.31.255",
        "46.34.96.0-46.34.127.255",
        "46.34.160.0-46.34.191.255",
        "46.36.96.0-46.36.111.255",
        "46.38.128.0-46.38.131.191",
        "46.38.132.0-46.38.159.255",
        "46.41.192.0-46.41.255.255",
        "46.51.0.0-46.51.127.255",
        "46.62.128.0-46.62.255.255",
        "46.100.0.0-46.100.255.255",
        "46.102.120.0-46.102.143.255",
        "46.102.184.0-46.102.187.255",
        "46.143.0.0-46.143.127.255",
        "46.143.204.0-46.143.215.255",
        "46.143.244.0-46.143.251.255",
        "46.148.32.0-46.148.47.255",
        "46.164.64.0-46.164.127.255",
        "46.167.128.0-46.167.159.255",
        "46.182.32.0-46.182.39.255",
        "46.209.0.0-46.209.94.255",
        "46.209.96.0-46.209.255.255",
        "46.224.0.0-46.225.255.255",
        "46.235.76.0-46.235.77.255",
        "46.245.0.0-46.245.127.255",
        "46.248.32.0-46.248.63.255",
        "46.249.96.0-46.249.96.255",
        "46.249.120.0-46.249.127.255",
        "46.255.216.0-46.255.223.255",
        "57.88.80.0-57.88.95.255",
        "62.32.49.128-62.32.49.231",
        "62.32.49.240-62.32.50.255",
        "62.32.53.64-62.32.53.127",
        "62.32.53.168-62.32.53.175",
        "62.32.53.224-62.32.53.239",
        "62.32.61.96-62.32.61.127",
        "62.32.61.224-62.32.61.255",
        "62.32.63.128-62.32.63.191",
        "62.60.128.0-62.60.159.255",
        "62.60.163.0-62.60.166.255",
        "62.60.168.0-62.60.169.255",
        "62.60.171.0-62.60.171.255",
        "62.60.175.0-62.60.175.255",
        "62.60.181.0-62.60.181.255",
        "62.60.186.0-62.60.186.255",
        "62.60.189.0-62.60.189.255",
        "62.60.191.0-62.60.200.255",
        "62.60.202.0-62.60.202.255",
        "62.60.205.0-62.60.205.255",
        "62.60.208.0-62.60.255.255",
        "62.102.128.0-62.102.143.255",
        "62.133.46.0-62.133.46.255",
        "62.193.0.0-62.193.31.255",
        "62.220.96.0-62.220.127.255",
        "63.243.185.0-63.243.185.255",
        "64.214.116.16-64.214.116.16",
        "66.79.96.0-66.79.127.255",
        "67.16.178.147-67.16.178.150",
        "69.194.64.0-69.194.127.255",
        "77.36.128.0-77.36.255.255",
        "77.42.0.0-77.42.127.255",
        "77.77.64.0-77.77.127.255",
        "77.81.32.0-77.81.47.255",
        "77.81.76.0-77.81.83.255",
        "77.81.128.0-77.81.135.255",
        "77.81.144.0-77.81.159.255",
        "77.81.192.0-77.81.223.255",
        "77.95.220.0-77.95.220.255",
        "77.104.64.0-77.104.127.255",
        "77.237.64.0-77.237.95.255",
        "77.237.160.0-77.237.191.255",
        "77.238.104.0-77.238.127.255",
        "77.245.224.0-77.245.239.255",
        "78.31.232.0-78.31.235.255",
        "78.38.0.0-78.39.255.255",
        "78.109.192.0-78.109.207.255",
        "78.110.112.0-78.110.127.255",
        "78.111.0.0-78.111.15.255",
        "78.154.32.0-78.154.63.255",
        "78.157.32.0-78.157.63.255",
        "78.158.160.0-78.158.191.255",
        "79.127.0.0-79.127.127.255",
        "79.132.192.0-79.132.193.255",
        "79.132.200.0-79.132.223.255",
        "79.143.84.0-79.143.86.255",
        "79.174.160.0-79.174.167.255",
        "79.175.128.0-79.175.167.133",
        "79.175.167.144-79.175.191.255",
        "80.66.176.0-80.66.191.255",
        "80.71.112.0-80.71.127.255",
        "80.75.0.0-80.75.15.255",
        "80.191.0.0-80.191.240.255",
        "80.191.241.128-80.191.255.255",
        "80.210.0.0-80.210.63.255",
        "80.210.128.0-80.210.255.255",
        "80.231.50.13-80.231.50.13",
        "80.242.0.0-80.242.15.255",
        "80.249.112.0-80.249.115.255",
        "80.250.192.0-80.250.207.255",
        "80.253.128.0-80.253.159.255",
        "80.255.3.160-80.255.3.191",
        "81.12.0.0-81.12.127.255",
        "81.16.112.0-81.16.127.255",
        "81.28.32.0-81.28.63.255",
        "81.29.240.0-81.29.255.255",
        "81.31.160.0-81.31.191.255",
        "81.31.224.0-81.31.246.255",
        "81.31.248.0-81.31.253.255",
        "81.90.144.0-81.90.159.255",
        "81.91.128.0-81.91.159.255",
        "81.92.216.0-81.92.216.255",
        "81.161.236.0-81.161.236.255",
        "81.163.0.0-81.163.7.255",
        "82.97.240.0-82.97.255.255",
        "82.99.192.0-82.99.255.255",
        "82.138.140.0-82.138.140.127",
        "82.180.192.0-82.180.255.255",
        "83.120.0.0-83.123.255.255",
        "83.147.192.0-83.147.194.255",
        "83.149.208.65-83.149.208.65",
        "83.150.192.0-83.150.195.255",
        "84.17.168.32-84.17.168.63",
        "84.47.192.0-84.47.255.255",
        "84.241.0.0-84.241.63.255",
        "85.9.64.0-85.9.127.255",
        "85.15.0.0-85.15.63.255",
        "85.133.128.0-85.133.255.255",
        "85.159.113.0-85.159.113.255",
        "85.185.0.0-85.185.255.255",
        "85.198.0.0-85.198.63.255",
        "85.204.76.0-85.204.77.255",
        "85.204.80.0-85.204.95.255",
        "85.204.104.0-85.204.105.255",
        "85.204.128.0-85.204.131.255",
        "85.204.208.0-85.204.223.255",
        "85.208.252.0-85.208.255.255",
        "85.239.192.0-85.239.223.255",
        "86.55.0.0-86.55.255.255",
        "86.57.0.0-86.57.127.255",
        "86.104.32.0-86.104.47.255",
        "86.104.80.0-86.104.111.255",
        "86.104.232.0-86.104.247.255",
        "86.105.40.0-86.105.47.255",
        "86.105.128.0-86.105.143.255",
        "86.106.24.0-86.106.25.255",
        "86.106.142.0-86.106.142.255",
        "86.106.192.0-86.106.199.255",
        "86.107.0.0-86.107.15.255",
        "86.107.80.0-86.107.95.255",
        "86.107.144.0-86.107.159.255",
        "86.107.172.0-86.107.175.255",
        "86.107.208.0-86.107.223.255",
        "86.109.32.0-86.109.63.255",
        "87.107.0.0-87.107.255.255",
        "87.128.22.75-87.128.22.75",
        "87.236.210.0-87.236.211.255",
        "87.236.213.0-87.236.214.255",
        "87.247.168.0-87.247.191.255",
        "87.248.128.0-87.248.128.255",
        "87.248.131.0-87.248.131.255",
        "87.248.134.0-87.248.135.255",
        "87.248.137.0-87.248.143.255",
        "87.248.145.0-87.248.157.255",
        "87.251.128.0-87.251.159.255",
        "88.131.195.45-88.131.195.45",
        "88.131.195.47-88.131.195.47",
        "88.131.240.122-88.131.240.123",
        "88.131.240.125-88.131.240.125",
        "88.131.244.10-88.131.244.10",
        "88.135.32.0-88.135.47.255",
        "88.135.68.0-88.135.68.255",
        "89.32.0.0-89.32.31.255",
        "89.32.96.0-89.32.111.255",
        "89.32.196.0-89.32.197.255",
        "89.32.248.0-89.32.251.255",
        "89.33.18.0-89.33.19.255",
        "89.33.100.0-89.33.103.255",
        "89.33.240.0-89.33.241.255",
        "89.34.20.0-89.34.21.255",
        "89.34.32.0-89.34.63.255",
        "89.34.128.0-89.34.159.255",
        "89.34.200.0-89.34.201.255",
        "89.34.248.0-89.34.255.255",
        "89.35.68.0-89.35.71.255",
        "89.35.120.0-89.35.123.255",
        "89.35.132.0-89.35.133.255",
        "89.35.180.0-89.35.183.255",
        "89.35.194.0-89.35.195.255",
        "89.36.16.0-89.36.17.255",
        "89.36.48.0-89.36.63.255",
        "89.36.96.0-89.36.111.255",
        "89.36.176.0-89.36.191.255",
        "89.36.194.0-89.36.195.255",
        "89.36.226.0-89.36.227.255",
        "89.36.252.0-89.36.253.255",
        "89.37.0.0-89.37.15.255",
        "89.37.102.0-89.37.103.255",
        "89.37.144.0-89.37.155.255",
        "89.37.168.0-89.37.171.255",
        "89.37.198.0-89.37.199.255",
        "89.37.208.0-89.37.211.255",
        "89.37.218.0-89.37.219.255",
        "89.37.240.0-89.37.255.255",
        "89.38.24.0-89.38.25.255",
        "89.38.80.0-89.38.95.255",
        "89.38.102.0-89.38.103.255",
        "89.38.184.0-89.38.199.255",
        "89.38.212.0-89.38.215.255",
        "89.38.244.0-89.38.247.255",
        "89.39.8.0-89.39.11.255",
        "89.39.208.0-89.39.208.255",
        "89.40.78.0-89.40.79.255",
        "89.40.106.0-89.40.107.255",
        "89.40.152.0-89.40.159.255",
        "89.40.240.0-89.40.255.255",
        "89.41.8.0-89.41.23.255",
        "89.41.40.0-89.41.43.255",
        "89.41.184.0-89.41.187.255",
        "89.41.192.0-89.41.223.255",
        "89.41.240.0-89.41.247.255",
        "89.42.44.0-89.42.47.255",
        "89.42.56.0-89.42.57.255",
        "89.42.68.0-89.42.69.255",
        "89.42.96.0-89.42.103.255",
        "89.42.136.0-89.42.139.255",
        "89.42.150.0-89.42.151.255",
        "89.42.184.0-89.42.191.255",
        "89.42.196.0-89.42.199.255",
        "89.42.208.0-89.42.210.188",
        "89.42.210.190-89.42.210.255",
        "89.42.228.0-89.42.229.255",
        "89.43.0.0-89.43.15.255",
        "89.43.36.0-89.43.37.255",
        "89.43.70.0-89.43.71.255",
        "89.43.88.0-89.43.103.255",
        "89.43.144.0-89.43.151.255",
        "89.43.182.0-89.43.183.255",
        "89.43.188.0-89.43.189.255",
        "89.43.216.0-89.43.231.255",
        "89.44.128.0-89.44.135.255",
        "89.44.176.0-89.44.183.255",
        "89.44.190.0-89.44.191.255",
        "89.44.240.0-89.44.243.255",
        "89.45.48.0-89.45.63.255",
        "89.45.80.0-89.45.81.255",
        "89.45.89.0-89.45.89.255",
        "89.45.112.0-89.45.119.255",
        "89.45.126.0-89.45.127.255",
        "89.45.152.0-89.45.159.255",
        "89.46.60.0-89.46.61.255",
        "89.46.94.0-89.46.95.255",
        "89.46.184.0-89.46.191.255",
        "89.46.216.0-89.46.219.255",
        "89.47.64.0-89.47.79.255",
        "89.47.128.0-89.47.159.255",
        "89.47.196.0-89.47.203.255",
        "89.144.128.0-89.144.191.255",
        "89.165.0.0-89.165.127.255",
        "89.196.0.0-89.196.255.255",
        "89.198.0.0-89.199.255.255",
        "89.219.64.0-89.219.127.255",
        "89.219.192.0-89.219.255.255",
        "89.221.80.0-89.221.95.255",
        "89.235.64.0-89.235.127.255",
        "91.92.104.0-91.92.104.255",
        "91.92.114.0-91.92.114.255",
        "91.92.121.0-91.92.127.255",
        "91.92.129.0-91.92.135.255",
        "91.92.145.0-91.92.151.255",
        "91.92.156.0-91.92.159.255",
        "91.92.164.0-91.92.167.255",
        "91.92.172.0-91.92.175.255",
        "91.92.180.0-91.92.193.255",
        "91.92.204.0-91.92.215.255",
        "91.92.220.0-91.92.223.255",
        "91.92.228.0-91.92.229.255",
        "91.92.231.0-91.92.231.255",
        "91.92.236.0-91.92.239.255",
        "91.98.0.0-91.99.255.255",
        "91.106.64.0-91.106.95.255",
        "91.107.128.0-91.107.255.255",
        "91.108.128.0-91.108.159.255",
        "91.109.104.0-91.109.111.255",
        "91.133.128.0-91.133.255.255",
        "91.147.64.0-91.147.79.255",
        "91.184.64.0-91.184.95.255",
        "91.185.128.0-91.185.159.255",
        "91.186.192.0-91.186.193.255",
        "91.190.88.0-91.190.95.255",
        "91.194.6.0-91.194.6.255",
        "91.199.9.0-91.199.9.255",
        "91.199.18.0-91.199.18.255",
        "91.199.27.0-91.199.27.255",
        "91.199.30.0-91.199.30.255",
        "91.206.122.0-91.206.123.255",
        "91.207.138.0-91.207.139.255",
        "91.208.165.0-91.208.165.255",
        "91.209.179.0-91.209.179.255",
        "91.209.183.0-91.209.184.255",
        "91.209.186.0-91.209.186.255",
        "91.209.242.0-91.209.242.255",
        "91.212.16.0-91.212.16.255",
        "91.212.252.0-91.212.252.255",
        "91.213.151.0-91.213.151.255",
        "91.213.157.0-91.213.157.255",
        "91.213.167.0-91.213.167.255",
        "91.213.172.0-91.213.172.255",
        "91.216.4.0-91.216.4.255",
        "91.217.64.0-91.217.65.255",
        "91.220.79.0-91.220.79.255",
        "91.220.113.0-91.220.113.255",
        "91.220.243.0-91.220.243.255",
        "91.221.116.0-91.221.117.255",
        "91.221.240.0-91.221.241.255",
        "91.222.196.0-91.222.199.255",
        "91.222.204.0-91.222.207.255",
        "91.224.20.0-91.224.21.255",
        "91.224.110.0-91.224.111.255",
        "91.224.176.0-91.224.177.255",
        "91.225.52.0-91.225.55.255",
        "91.226.224.0-91.226.225.255",
        "91.227.84.0-91.227.87.255",
        "91.227.246.0-91.227.247.255",
        "91.228.22.0-91.228.23.255",
        "91.228.132.0-91.228.133.255",
        "91.228.189.0-91.228.189.255",
        "91.229.46.0-91.229.47.255",
        "91.229.214.0-91.229.215.255",
        "91.230.32.0-91.230.32.255",
        "91.232.64.0-91.232.69.255",
        "91.232.72.0-91.232.75.255",
        "91.233.56.0-91.233.59.255",
        "91.236.168.0-91.236.169.255",
        "91.237.254.0-91.238.0.255",
        "91.238.92.0-91.238.93.255",
        "91.239.14.0-91.239.14.255",
        "91.239.108.0-91.239.111.255",
        "91.239.148.0-91.239.149.255",
        "91.239.214.0-91.239.214.255",
        "91.240.60.0-91.240.63.255",
        "91.240.180.0-91.240.183.255",
        "91.241.20.0-91.241.21.255",
        "91.241.92.0-91.241.92.255",
        "91.242.44.0-91.242.45.255",
        "91.243.126.0-91.243.127.255",
        "91.243.160.0-91.243.175.255",
        "91.244.120.0-91.244.123.255",
        "91.245.228.0-91.245.231.255",
        "91.247.66.0-91.247.67.255",
        "91.250.224.0-91.250.239.255",
        "91.251.0.0-91.251.255.255",
        "92.42.48.0-92.42.55.255",
        "92.43.160.0-92.43.163.255",
        "92.61.176.0-92.61.183.255",
        "92.61.185.0-92.61.188.255",
        "92.61.190.0-92.61.190.255",
        "92.114.16.0-92.114.31.255",
        "92.114.48.0-92.114.51.255",
        "92.114.64.0-92.114.79.255",
        "92.119.57.0-92.119.59.255",
        "92.119.68.0-92.119.71.255",
        "92.242.192.0-92.242.221.255",
        "92.242.223.0-92.242.223.255",
        "92.246.144.0-92.246.147.255",
        "92.246.156.0-92.246.159.255",
        "92.249.56.0-92.249.59.255",
        "93.88.64.0-93.88.73.255",
        "93.110.0.0-93.110.255.255",
        "93.113.224.0-93.113.239.255",
        "93.114.16.0-93.114.31.255",
        "93.114.104.0-93.114.111.255",
        "93.115.120.0-93.115.127.255",
        "93.115.144.0-93.115.151.255",
        "93.115.216.0-93.115.239.255",
        "93.117.0.0-93.117.47.255",
        "93.117.96.0-93.117.127.255",
        "93.117.176.0-93.117.191.255",
        "93.118.96.0-93.118.175.255",
        "93.118.180.0-93.118.187.255",
        "93.119.32.0-93.119.95.255",
        "93.119.208.0-93.119.223.255",
        "93.126.0.0-93.126.63.255",
        "93.190.24.0-93.190.31.255",
        "94.24.0.0-94.24.23.255",
        "94.24.80.0-94.24.103.255",
        "94.74.128.0-94.74.191.255",
        "94.101.120.0-94.101.123.255",
        "94.101.128.0-94.101.143.255",
        "94.101.176.0-94.101.191.255",
        "94.101.240.0-94.101.255.255",
        "94.139.160.0-94.139.191.255",
        "94.176.8.0-94.176.15.255",
        "94.176.32.0-94.176.39.255",
        "94.177.72.0-94.177.79.255",
        "94.182.0.0-94.184.255.255",
        "94.199.136.0-94.199.139.255",
        "94.232.168.0-94.232.175.255",
        "94.241.136.0-94.241.143.255",
        "94.241.160.0-94.241.167.255",
        "95.38.0.0-95.38.255.255",
        "95.64.0.0-95.64.127.255",
        "95.80.128.0-95.80.191.255",
        "95.81.64.0-95.81.127.255",
        "95.82.0.0-95.82.63.255",
        "95.130.56.0-95.130.63.255",
        "95.130.240.0-95.130.247.255",
        "95.142.224.0-95.142.239.255",
        "95.156.252.0-95.156.255.255",
        "95.162.0.0-95.162.255.255",
        "95.215.160.0-95.215.163.255",
        "95.215.173.0-95.215.173.255",
        "103.130.144.0-103.130.147.255",
        "103.215.220.0-103.215.220.255",
        "103.215.223.0-103.215.223.255",
        "103.216.60.0-103.216.62.255",
        "103.231.136.0-103.231.138.255",
        "109.70.237.0-109.70.237.255",
        "109.72.192.0-109.72.207.255",
        "109.74.232.0-109.74.239.255",
        "109.94.164.0-109.94.167.255",
        "109.95.60.0-109.95.71.255",
        "109.108.160.0-109.108.191.255",
        "109.109.32.0-109.109.41.255",
        "109.109.43.0-109.109.63.255",
        "109.110.161.0-109.110.166.255",
        "109.110.168.0-109.110.170.255",
        "109.110.173.0-109.110.175.255",
        "109.110.177.0-109.110.177.255",
        "109.110.180.0-109.110.182.255",
        "109.110.189.0-109.110.189.255",
        "109.111.32.0-109.111.35.255",
        "109.111.39.0-109.111.63.255",
        "109.122.192.0-109.122.199.255",
        "109.122.201.0-109.122.204.255",
        "109.122.206.0-109.122.206.255",
        "109.122.208.0-109.122.222.255",
        "109.122.224.0-109.122.247.255",
        "109.122.250.0-109.122.253.255",
        "109.125.128.0-109.125.191.255",
        "109.162.128.0-109.162.255.255",
        "109.201.0.0-109.201.31.255",
        "109.203.128.0-109.203.191.255",
        "109.206.252.0-109.206.255.255",
        "109.225.128.0-109.225.191.255",
        "109.230.64.0-109.230.111.255",
        "109.232.0.0-109.232.7.255",
        "109.238.176.0-109.238.191.255",
        "109.239.0.0-109.239.15.255",
        "113.203.0.0-113.203.127.255",
        "128.65.160.0-128.65.191.255",
        "128.140.0.0-128.140.127.255",
        "130.185.72.0-130.185.79.255",
        "130.244.71.67-130.244.71.67",
        "130.244.71.72-130.244.71.74",
        "130.244.71.80-130.244.71.81",
        "130.244.85.151-130.244.85.151",
        "130.255.192.0-130.255.255.255",
        "146.66.128.0-146.66.135.255",
        "151.232.0.0-151.235.255.255",
        "151.238.0.0-151.247.255.255",
        "152.89.12.0-152.89.15.255",
        "152.89.44.0-152.89.47.255",
        "152.89.152.0-152.89.155.255",
        "157.119.188.0-157.119.191.255",
        "158.58.0.0-158.58.127.255",
        "158.58.184.0-158.58.191.255",
        "159.20.96.0-159.20.111.255",
        "164.138.16.0-164.138.23.255",
        "164.138.128.0-164.138.191.255",
        "164.215.56.0-164.215.63.255",
        "164.215.128.0-164.215.255.255",
        "170.248.16.15-170.248.16.15",
        "171.22.24.0-171.22.27.255",
        "172.80.128.0-172.80.255.255",
        "176.12.64.0-176.12.79.255",
        "176.46.128.0-176.46.159.255",
        "176.56.144.0-176.56.159.255",
        "176.62.144.0-176.62.151.255",
        "176.65.160.0-176.65.255.255",
        "176.67.64.0-176.67.79.255",
        "176.101.32.0-176.101.55.255",
        "176.102.224.0-176.102.255.255",
        "176.122.210.0-176.122.211.255",
        "176.123.64.0-176.123.127.255",
        "176.124.64.0-176.124.67.255",
        "176.221.16.0-176.221.31.255",
        "176.221.64.0-176.221.71.255",
        "176.223.80.0-176.223.87.255",
        "178.21.40.0-178.21.47.255",
        "178.21.160.0-178.21.167.255",
        "178.22.72.0-178.22.79.255",
        "178.22.120.0-178.22.127.255",
        "178.131.0.0-178.131.255.255",
        "178.157.0.0-178.157.1.255",
        "178.169.0.0-178.169.31.255",
        "178.173.128.0-178.173.223.255",
        "178.215.0.0-178.215.63.255",
        "178.216.248.0-178.216.255.255",
        "178.219.224.0-178.219.239.255",
        "178.236.32.0-178.236.35.255",
        "178.236.96.0-178.236.111.255",
        "178.238.192.0-178.238.207.255",
        "178.239.144.0-178.239.159.255",
        "178.248.40.0-178.248.47.255",
        "178.251.208.0-178.251.215.255",
        "178.252.128.0-178.252.191.255",
        "178.253.16.0-178.253.16.255",
        "178.253.22.0-178.253.23.255",
        "178.253.26.0-178.253.27.255",
        "178.253.31.0-178.253.31.255",
        "178.253.38.0-178.253.45.255",
        "185.1.77.0-185.1.77.255",
        "185.2.12.0-185.2.15.255",
        "185.3.124.0-185.3.127.255",
        "185.3.200.0-185.3.203.255",
        "185.3.212.0-185.3.215.255",
        "185.4.0.0-185.4.3.255",
        "185.4.16.0-185.4.19.255",
        "185.4.28.0-185.4.31.255",
        "185.4.104.0-185.4.107.255",
        "185.4.220.0-185.4.223.255",
        "185.5.156.0-185.5.159.255",
        "185.8.172.0-185.8.175.255",
        "185.10.71.0-185.10.75.255",
        "185.11.68.0-185.11.71.255",
        "185.11.88.0-185.11.91.255",
        "185.11.176.0-185.11.179.255",
        "185.12.60.0-185.12.63.255",
        "185.12.100.0-185.12.102.255",
        "185.13.228.0-185.13.231.255",
        "185.14.80.0-185.14.83.255",
        "185.14.160.0-185.14.163.255",
        "185.16.232.0-185.16.235.255",
        "185.18.156.0-185.18.159.255",
        "185.18.212.0-185.18.215.255",
        "185.20.160.0-185.20.163.255",
        "185.21.68.0-185.21.71.255",
        "185.21.76.0-185.21.79.255",
        "185.22.28.0-185.22.31.255",
        "185.23.128.0-185.23.131.255",
        "185.24.136.0-185.24.139.255",
        "185.24.149.0-185.24.151.255",
        "185.24.228.0-185.24.231.255",
        "185.24.252.0-185.24.255.255",
        "185.25.172.0-185.25.175.255",
        "185.26.32.0-185.26.35.255",
        "185.26.232.0-185.26.235.255",
        "185.30.4.0-185.30.7.255",
        "185.30.76.0-185.30.79.255",
        "185.31.124.0-185.31.127.255",
        "185.32.128.0-185.32.131.255",
        "185.34.160.0-185.34.163.255",
        "185.37.52.0-185.37.55.255",
        "185.39.180.0-185.39.183.255",
        "185.40.16.0-185.40.16.255",
        "185.40.240.0-185.40.243.255",
        "185.41.0.0-185.41.3.255",
        "185.41.220.0-185.41.223.255",
        "185.42.24.0-185.42.27.255",
        "185.42.212.0-185.42.215.255",
        "185.42.224.0-185.42.227.255",
        "185.44.36.0-185.44.39.255",
        "185.44.100.0-185.44.103.255",
        "185.44.112.0-185.44.115.255",
        "185.45.188.0-185.45.191.255",
        "185.46.0.0-185.46.3.255",
        "185.46.108.0-185.46.111.255",
        "185.46.216.0-185.46.219.255",
        "185.47.48.0-185.47.51.255",
        "185.49.84.0-185.49.87.255",
        "185.49.96.0-185.49.99.255",
        "185.49.104.0-185.49.107.255",
        "185.49.231.0-185.49.231.255",
        "185.50.36.0-185.50.36.0",
        "185.50.37.0-185.50.39.255",
        "185.51.40.0-185.51.43.255",
        "185.51.200.0-185.51.203.255",
        "185.53.140.0-185.53.143.255",
        "185.55.224.0-185.55.227.255",
        "185.56.92.0-185.56.99.255",
        "185.57.132.0-185.57.135.255",
        "185.57.164.0-185.57.167.255",
        "185.57.200.0-185.57.203.255",
        "185.58.240.0-185.58.243.255",
        "185.59.112.0-185.59.113.255",
        "185.60.32.0-185.60.35.255",
        "185.60.136.0-185.60.139.255",
        "185.62.232.0-185.62.235.255",
        "185.63.236.0-185.63.239.255",
        "185.64.176.0-185.64.179.255",
        "185.66.224.0-185.66.231.255",
        "185.67.12.0-185.67.15.255",
        "185.67.100.0-185.67.103.255",
        "185.67.156.0-185.67.159.255",
        "185.67.212.0-185.67.215.255",
        "185.69.108.0-185.69.111.255",
        "185.70.60.0-185.70.63.255",
        "185.71.152.0-185.71.155.255",
        "185.71.192.0-185.71.195.255",
        "185.72.24.0-185.72.27.255",
        "185.72.80.0-185.72.83.255",
        "185.73.0.0-185.73.3.255",
        "185.73.76.0-185.73.79.255",
        "185.73.112.0-185.73.112.255",
        "185.73.114.0-185.73.114.255",
        "185.73.226.0-185.73.226.255",
        "185.74.164.0-185.74.167.255",
        "185.75.196.0-185.75.199.255",
        "185.75.204.0-185.75.207.255",
        "185.76.248.0-185.76.251.255",
        "185.78.20.0-185.78.23.255",
        "185.79.60.0-185.79.63.255",
        "185.79.96.0-185.79.99.255",
        "185.79.156.0-185.79.159.255",
        "185.80.100.0-185.80.103.255",
        "185.80.197.0-185.80.199.255",
        "185.81.40.0-185.81.43.255",
        "185.81.96.0-185.81.97.255",
        "185.81.99.0-185.81.99.255",
        "185.82.28.0-185.82.31.255",
        "185.82.64.0-185.82.67.255",
        "185.82.136.0-185.82.139.255",
        "185.82.164.0-185.82.167.255",
        "185.82.180.0-185.82.183.255",
        "185.83.28.0-185.83.31.255",
        "185.83.76.0-185.83.83.255",
        "185.83.88.0-185.83.91.255",
        "185.83.112.0-185.83.115.255",
        "185.83.180.0-185.83.187.255",
        "185.83.196.0-185.83.203.255",
        "185.83.208.0-185.83.211.255",
        "185.84.220.0-185.84.223.255",
        "185.85.68.0-185.85.71.255",
        "185.85.136.0-185.85.139.255",
        "185.86.36.0-185.86.39.255",
        "185.86.180.0-185.86.183.255",
        "185.88.11.0-185.88.11.255",
        "185.88.48.0-185.88.51.255",
        "185.88.152.0-185.88.155.255",
        "185.88.176.0-185.88.179.255",
        "185.88.252.0-185.88.255.255",
        "185.89.112.0-185.89.115.255",
        "185.92.4.0-185.92.11.255",
        "185.92.40.0-185.92.43.255",
        "185.94.96.0-185.94.99.127",
        "185.94.99.136-185.94.99.255",
        "185.95.60.0-185.95.63.255",
        "185.95.152.0-185.95.155.255",
        "185.95.180.0-185.95.183.255",
        "185.96.240.0-185.96.243.255",
        "185.97.116.0-185.97.119.255",
        "185.98.112.0-185.98.115.255",
        "185.99.212.0-185.99.215.255",
        "185.100.44.0-185.100.47.255",
        "185.101.228.0-185.101.231.255",
        "185.103.84.0-185.103.87.255",
        "185.103.128.0-185.103.131.255",
        "185.103.244.0-185.103.251.255",
        "185.104.192.0-185.104.192.255",
        "185.104.228.0-185.104.235.255",
        "185.104.240.0-185.104.243.255",
        "185.105.100.0-185.105.103.255",
        "185.105.120.0-185.105.123.255",
        "185.105.184.0-185.105.187.255",
        "185.105.236.0-185.105.239.255",
        "185.106.136.0-185.106.139.255",
        "185.106.144.0-185.106.147.255",
        "185.106.200.0-185.106.203.255",
        "185.106.228.0-185.106.231.255",
        "185.107.28.0-185.107.33.255",
        "185.107.244.0-185.107.251.255",
        "185.108.96.0-185.108.99.255",
        "185.108.164.0-185.108.167.255",
        "185.109.60.0-185.109.63.255",
        "185.109.72.0-185.109.75.255",
        "185.109.80.0-185.109.83.255",
        "185.109.128.0-185.109.131.255",
        "185.109.244.0-185.109.251.255",
        "185.110.28.0-185.110.31.255",
        "185.110.216.0-185.110.219.255",
        "185.110.228.0-185.110.231.255",
        "185.110.236.0-185.110.239.255",
        "185.110.244.0-185.110.247.255",
        "185.110.252.120-185.110.252.255",
        "185.110.254.0-185.110.255.255",
        "185.111.8.0-185.111.15.255",
        "185.111.64.0-185.111.67.255",
        "185.111.80.0-185.111.83.255",
        "185.111.136.0-185.111.139.255",
        "185.112.32.0-185.112.39.255",
        "185.112.130.0-185.112.131.255",
        "185.112.148.0-185.112.151.255",
        "185.112.168.0-185.112.171.255",
        "185.113.56.0-185.113.59.255",
        "185.113.112.0-185.113.115.255",
        "185.114.188.0-185.114.191.255",
        "185.115.76.0-185.115.79.255",
        "185.115.148.0-185.115.151.255",
        "185.115.168.0-185.115.171.255",
        "185.116.20.0-185.116.27.255",
        "185.116.44.0-185.116.47.255",
        "185.116.160.0-185.116.163.255",
        "185.117.48.0-185.117.51.255",
        "185.117.136.0-185.117.139.255",
        "185.117.204.0-185.117.207.255",
        "185.118.12.0-185.118.15.255",
        "185.118.136.0-185.118.139.255",
        "185.118.154.0-185.118.155.255",
        "185.119.4.0-185.119.7.255",
        "185.119.164.0-185.119.167.255",
        "185.119.240.0-185.119.243.255",
        "185.120.120.0-185.120.123.255",
        "185.120.136.0-185.120.139.255",
        "185.120.160.0-185.120.163.255",
        "185.120.168.0-185.120.171.255",
        "185.120.192.0-185.120.203.255",
        "185.120.208.0-185.120.251.255",
        "185.121.56.0-185.121.59.255",
        "185.121.128.0-185.121.131.255",
        "185.122.80.0-185.122.83.255",
        "185.123.68.0-185.123.71.255",
        "185.123.208.0-185.123.211.255",
        "185.124.112.0-185.124.115.255",
        "185.124.156.0-185.124.159.255",
        "185.124.172.0-185.124.175.255",
        "185.125.20.0-185.125.23.255",
        "185.125.244.0-185.126.19.255",
        "185.126.40.0-185.126.43.255",
        "185.126.132.0-185.126.135.255",
        "185.126.156.0-185.126.159.255",
        "185.126.200.0-185.126.203.255",
        "185.127.232.0-185.127.235.255",
        "185.128.48.0-185.128.51.255",
        "185.128.80.0-185.128.83.255",
        "185.128.136.0-185.128.139.255",
        "185.128.152.0-185.128.155.255",
        "185.128.164.0-185.128.167.255",
        "185.129.80.0-185.129.83.255",
        "185.129.168.0-185.129.171.255",
        "185.129.184.0-185.129.191.255",
        "185.129.196.0-185.129.203.255",
        "185.129.212.0-185.129.219.255",
        "185.129.228.0-185.129.243.255",
        "185.130.76.0-185.130.79.255",
        "185.131.28.0-185.131.31.255",
        "185.131.84.0-185.131.95.255",
        "185.131.100.0-185.131.103.255",
        "185.131.108.0-185.131.119.255",
        "185.131.124.0-185.131.131.255",
        "185.131.136.0-185.131.143.255",
        "185.131.148.0-185.131.159.255",
        "185.131.164.0-185.131.171.255",
        "185.132.80.0-185.132.83.255",
        "185.132.212.0-185.132.215.255",
        "185.133.152.0-185.133.155.255",
        "185.133.164.0-185.133.167.255",
        "185.133.244.0-185.133.246.255",
        "185.134.96.0-185.134.99.255",
        "185.135.28.0-185.135.31.255",
        "185.135.228.0-185.135.231.255",
        "185.136.100.0-185.136.103.255",
        "185.136.172.0-185.136.175.255",
        "185.136.180.0-185.136.183.255",
        "185.136.192.0-185.136.195.255",
        "185.136.220.0-185.136.223.255",
        "185.137.24.0-185.137.27.255",
        "185.137.60.0-185.137.63.255",
        "185.137.108.0-185.137.110.255",
        "185.139.64.0-185.139.67.255",
        "185.140.4.0-185.140.7.255",
        "185.140.56.0-185.140.59.255",
        "185.140.232.0-185.140.235.255",
        "185.140.240.0-185.140.243.255",
        "185.141.36.0-185.141.39.255",
        "185.141.48.0-185.141.51.255",
        "185.141.104.0-185.141.107.255",
        "185.141.132.0-185.141.135.255",
        "185.141.168.0-185.141.171.255",
        "185.141.212.0-185.141.215.255",
        "185.141.244.0-185.141.247.255",
        "185.142.92.0-185.142.95.255",
        "185.142.124.0-185.142.127.255",
        "185.142.156.0-185.142.159.255",
        "185.142.232.0-185.142.235.255",
        "185.143.204.0-185.143.207.255",
        "185.144.64.0-185.144.67.255",
        "185.145.8.0-185.145.11.255",
        "185.145.184.0-185.145.187.255",
        "185.147.40.0-185.147.43.255",
        "185.147.84.0-185.147.87.255",
        "185.147.160.0-185.147.163.255",
        "185.147.176.0-185.147.179.255",
        "185.150.108.0-185.150.111.255",
        "185.151.96.0-185.151.99.255",
        "185.153.184.0-185.153.187.255",
        "185.153.208.0-185.153.211.255",
        "185.154.184.0-185.154.187.255",
        "185.155.8.0-185.155.15.255",
        "185.155.72.0-185.155.75.255",
        "185.155.236.0-185.155.239.255",
        "185.156.44.0-185.156.47.255",
        "185.157.8.0-185.157.11.255",
        "185.158.172.0-185.158.175.255",
        "185.159.152.0-185.159.155.255",
        "185.159.176.0-185.159.179.255",
        "185.160.104.0-185.160.107.255",
        "185.160.176.0-185.160.179.255",
        "185.161.36.0-185.161.39.255",
        "185.161.112.0-185.161.115.255",
        "185.162.40.0-185.162.43.255",
        "185.162.216.0-185.162.219.255",
        "185.163.88.0-185.163.91.255",
        "185.164.73.0-185.164.75.255",
        "185.164.252.0-185.164.255.255",
        "185.165.28.0-185.165.31.255",
        "185.165.40.0-185.165.43.255",
        "185.165.100.0-185.165.103.255",
        "185.165.116.0-185.165.119.255",
        "185.165.204.0-185.165.207.255",
        "185.166.60.0-185.166.63.255",
        "185.166.104.0-185.166.107.255",
        "185.166.112.0-185.166.115.255",
        "185.167.72.0-185.167.75.255",
        "185.167.100.0-185.167.103.255",
        "185.167.124.0-185.167.127.255",
        "185.168.28.0-185.168.31.255",
        "185.169.6.0-185.169.6.255",
        "185.169.20.0-185.169.23.255",
        "185.169.36.0-185.169.39.255",
        "185.170.236.0-185.170.239.255",
        "185.171.52.0-185.171.55.255",
        "185.172.0.0-185.172.3.255",
        "185.172.68.0-185.172.71.255",
        "185.172.212.0-185.172.215.255",
        "185.173.104.0-185.173.107.255",
        "185.173.168.0-185.173.171.255",
        "185.174.132.0-185.174.135.255",
        "185.174.200.0-185.174.203.255",
        "185.174.248.0-185.174.251.255",
        "185.175.76.0-185.175.79.255",
        "185.175.240.0-185.175.243.255",
        "185.176.32.0-185.176.35.255",
        "185.176.56.0-185.176.59.255",
        "185.177.156.0-185.177.159.255",
        "185.177.232.0-185.177.235.255",
        "185.178.104.0-185.178.107.255",
        "185.178.220.0-185.178.223.255",
        "185.179.168.0-185.179.171.255",
        "185.179.220.0-185.179.223.255",
        "185.180.52.0-185.180.55.255",
        "185.180.128.0-185.180.131.255",
        "185.181.180.0-185.181.183.255",
        "185.182.220.0-185.182.223.255",
        "185.182.248.0-185.182.251.255",
        "185.183.128.0-185.183.131.255",
        "185.184.32.0-185.184.35.255",
        "185.184.48.0-185.184.51.255",
        "185.185.16.0-185.185.19.255",
        "185.185.240.0-185.185.243.255",
        "185.186.48.0-185.186.51.255",
        "185.186.240.0-185.186.243.255",
        "185.187.48.0-185.187.51.255",
        "185.187.84.0-185.187.87.255",
        "185.188.104.0-185.188.107.255",
        "185.188.112.0-185.188.115.255",
        "185.189.120.0-185.189.123.255",
        "185.190.20.0-185.190.23.255",
        "185.190.39.0-185.190.39.255",
        "185.191.76.0-185.191.79.255",
        "185.192.8.0-185.192.11.255",
        "185.192.112.0-185.192.115.255",
        "185.193.208.0-185.193.211.255",
        "185.194.76.0-185.194.79.255",
        "185.194.244.0-185.194.247.255",
        "185.195.72.0-185.195.75.255",
        "185.196.148.0-185.196.151.255",
        "185.197.68.0-185.197.71.255",
        "185.197.112.0-185.197.115.255",
        "185.198.160.0-185.198.163.255",
        "185.198.252.0-185.198.255.255",
        "185.199.64.0-185.199.67.255",
        "185.199.208.0-185.199.211.255",
        "185.201.48.0-185.201.51.255",
        "185.202.56.0-185.202.59.255",
        "185.202.92.0-185.202.95.255",
        "185.203.160.0-185.203.163.255",
        "185.204.168.0-185.204.171.255",
        "185.204.180.0-185.204.183.255",
        "185.205.203.0-185.205.203.255",
        "185.205.220.0-185.205.223.255",
        "185.206.92.0-185.206.95.255",
        "185.206.236.0-185.206.239.255",
        "185.207.4.0-185.207.7.255",
        "185.207.52.0-185.207.55.255",
        "185.207.72.0-185.207.75.255",
        "185.208.76.0-185.208.79.255",
        "185.208.148.0-185.208.151.255",
        "185.208.174.0-185.208.175.255",
        "185.208.180.0-185.208.183.255",
        "185.209.188.0-185.209.191.255",
        "185.210.200.0-185.210.203.255",
        "185.211.56.0-185.211.59.255",
        "185.211.84.0-185.211.91.255",
        "185.212.48.0-185.212.51.255",
        "185.212.192.0-185.212.195.255",
        "185.213.8.0-185.213.11.255",
        "185.213.164.0-185.213.167.255",
        "185.214.36.0-185.214.39.255",
        "185.215.124.0-185.215.127.255",
        "185.215.152.0-185.215.155.255",
        "185.215.228.0-185.215.231.255",
        "185.215.244.0-185.215.246.255",
        "185.216.124.0-185.216.127.255",
        "185.217.39.0-185.217.39.255",
        "185.217.160.0-185.217.163.255",
        "185.219.112.0-185.219.115.255",
        "185.220.224.0-185.220.227.255",
        "185.221.112.0-185.221.115.255",
        "185.221.192.0-185.221.195.255",
        "185.221.239.0-185.221.239.255",
        "185.222.120.0-185.222.123.255",
        "185.222.180.0-185.222.187.255",
        "185.222.210.0-185.222.210.255",
        "185.223.160.0-185.223.160.255",
        "185.224.176.0-185.224.179.255",
        "185.225.80.0-185.225.83.255",
        "185.225.180.0-185.225.183.255",
        "185.225.240.0-185.225.243.255",
        "185.226.97.0-185.226.97.255",
        "185.226.116.0-185.226.119.255",
        "185.226.132.0-185.226.135.255",
        "185.226.140.0-185.226.143.255",
        "185.227.64.0-185.227.67.255",
        "185.227.116.0-185.227.119.255",
        "185.228.236.0-185.228.239.255",
        "185.229.0.0-185.229.3.255",
        "185.229.28.0-185.229.31.255",
        "185.229.204.0-185.229.204.255",
        "185.231.65.0-185.231.65.255",
        "185.231.112.0-185.231.112.255",
        "185.231.114.0-185.231.115.255",
        "185.231.180.0-185.231.183.255",
        "185.232.152.0-185.232.155.255",
        "185.232.176.0-185.232.179.255",
        "185.233.12.0-185.233.15.255",
        "185.233.84.0-185.233.87.255",
        "185.234.192.0-185.234.195.255",
        "185.235.136.0-185.235.139.255",
        "185.236.36.0-185.236.39.255",
        "185.236.88.0-185.236.91.255",
        "185.237.8.0-185.237.11.255",
        "185.237.84.0-185.237.87.255",
        "185.238.20.0-185.238.23.255",
        "185.238.44.0-185.238.47.255",
        "185.238.92.0-185.238.95.255",
        "185.239.0.0-185.239.3.255",
        "185.239.104.0-185.239.107.255",
        "185.240.56.0-185.240.59.255",
        "185.240.148.0-185.240.151.255",
        "185.243.48.0-185.243.51.255",
        "185.244.52.0-185.244.55.255",
        "185.246.4.0-185.246.7.255",
        "185.248.32.0-185.248.32.255",
        "185.251.76.0-185.251.79.255",
        "185.252.28.0-185.252.31.255",
        "185.252.200.0-185.252.200.255",
        "185.254.165.0-185.254.166.255",
        "185.255.68.0-185.255.71.255",
        "185.255.88.0-185.255.91.255",
        "185.255.208.0-185.255.211.255",
        "188.0.240.0-188.0.255.255",
        "188.34.0.0-188.34.127.255",
        "188.75.64.0-188.75.127.255",
        "188.94.188.0-188.94.188.255",
        "188.95.89.0-188.95.89.255",
        "188.118.64.0-188.118.127.255",
        "188.121.96.0-188.121.159.255",
        "188.122.96.0-188.122.127.255",
        "188.136.128.0-188.136.223.255",
        "188.158.0.0-188.159.255.255",
        "188.191.176.0-188.191.183.255",
        "188.208.56.0-188.208.95.255",
        "188.208.144.0-188.208.191.255",
        "188.208.200.0-188.208.203.255",
        "188.208.208.0-188.208.215.255",
        "188.208.224.0-188.209.47.255",
        "188.209.64.0-188.209.79.255",
        "188.209.128.0-188.209.143.255",
        "188.209.152.0-188.209.153.255",
        "188.209.192.0-188.209.207.255",
        "188.210.64.0-188.210.87.255",
        "188.210.96.0-188.210.207.255",
        "188.210.232.0-188.210.235.255",
        "188.211.0.0-188.211.15.255",
        "188.211.32.0-188.211.159.255",
        "188.211.176.0-188.211.223.255",
        "188.212.6.0-188.212.7.255",
        "188.212.22.0-188.212.22.255",
        "188.212.48.0-188.212.99.255",
        "188.212.144.0-188.212.151.255",
        "188.212.160.0-188.212.191.255",
        "188.212.200.0-188.212.247.255",
        "188.213.64.0-188.213.79.255",
        "188.213.96.0-188.213.127.255",
        "188.213.144.0-188.213.159.255",
        "188.213.176.0-188.213.199.255",
        "188.213.208.0-188.213.211.255",
        "188.214.4.0-188.214.7.255",
        "188.214.84.0-188.214.87.255",
        "188.214.96.0-188.214.99.255",
        "188.214.120.0-188.214.121.255",
        "188.214.160.0-188.214.191.255",
        "188.214.216.0-188.214.223.255",
        "188.214.232.0-188.214.239.255",
        "188.215.24.0-188.215.27.255",
        "188.215.88.0-188.215.91.255",
        "188.215.128.0-188.215.143.255",
        "188.215.160.0-188.215.223.255",
        "188.215.240.0-188.215.243.255",
        "188.229.0.0-188.229.127.255",
        "188.240.196.0-188.240.196.255",
        "188.240.212.0-188.240.212.255",
        "188.240.248.0-188.240.255.255",
        "188.245.112.0-188.245.112.255",
        "188.245.176.0-188.245.176.255",
        "188.253.2.0-188.253.127.255",
        "192.15.0.0-192.15.255.255",
        "192.167.140.66-192.167.140.66",
        "193.0.156.0-193.0.156.255",
        "193.3.31.0-193.3.31.255",
        "193.3.231.0-193.3.231.255",
        "193.3.255.0-193.3.255.255",
        "193.8.139.0-193.8.139.255",
        "193.19.144.0-193.19.145.255",
        "193.22.20.0-193.22.20.255",
        "193.26.14.0-193.26.14.255",
        "193.28.181.0-193.28.181.255",
        "193.29.17.0-193.29.18.255",
        "193.29.24.0-193.29.24.255",
        "193.29.26.0-193.29.26.255",
        "193.32.80.0-193.32.81.255",
        "193.34.244.0-193.34.247.255",
        "193.35.62.0-193.35.62.255",
        "193.56.59.0-193.56.59.255",
        "193.56.61.0-193.56.61.255",
        "193.56.107.0-193.56.107.255",
        "193.56.118.0-193.56.118.255",
        "193.104.22.0-193.104.22.255",
        "193.104.212.0-193.104.212.255",
        "193.105.2.0-193.105.2.255",
        "193.105.6.0-193.105.6.255",
        "193.105.234.0-193.105.234.255",
        "193.108.242.0-193.108.243.255",
        "193.111.234.0-193.111.235.255",
        "193.134.100.0-193.134.101.255",
        "193.141.64.0-193.141.65.255",
        "193.141.126.0-193.141.127.255",
        "193.142.30.0-193.142.30.255",
        "193.142.232.0-193.142.233.255",
        "193.142.254.0-193.142.255.255",
        "193.148.64.0-193.148.67.255",
        "193.151.128.0-193.151.159.255",
        "193.176.240.0-193.176.243.255",
        "193.178.200.0-193.178.203.255",
        "193.189.122.0-193.189.123.255",
        "193.200.148.0-193.200.148.255",
        "193.201.192.0-193.201.195.255",
        "193.222.51.0-193.222.51.255",
        "193.228.91.0-193.228.91.255",
        "193.239.196.0-193.239.197.255",
        "193.239.236.0-193.239.237.255",
        "193.242.194.0-193.242.195.255",
        "193.242.208.0-193.242.209.255",
        "193.246.160.0-193.246.161.255",
        "193.246.164.0-193.246.165.255",
        "193.246.174.0-193.246.175.255",
        "193.246.200.0-193.246.201.255",
        "194.5.40.0-194.5.43.255",
        "194.5.175.0-194.5.179.255",
        "194.5.188.0-194.5.188.255",
        "194.5.195.0-194.5.195.255",
        "194.5.205.0-194.5.205.255",
        "194.9.56.0-194.9.57.255",
        "194.9.80.0-194.9.81.255",
        "194.15.96.0-194.15.99.255",
        "194.26.2.0-194.26.3.255",
        "194.26.20.0-194.26.21.255",
        "194.33.104.0-194.33.107.255",
        "194.33.122.0-194.33.127.255",
        "194.34.163.0-194.34.163.255",
        "194.36.174.0-194.36.174.255",
        "194.39.36.0-194.39.39.255",
        "194.41.48.0-194.41.51.255",
        "194.50.204.0-194.50.204.255",
        "194.50.209.0-194.50.209.255",
        "194.50.216.0-194.50.216.255",
        "194.50.218.0-194.50.218.255",
        "194.53.118.0-194.53.119.255",
        "194.53.122.0-194.53.123.255",
        "194.59.170.0-194.59.171.255",
        "194.59.214.0-194.59.215.255",
        "194.60.208.0-194.60.211.255",
        "194.60.228.0-194.60.231.255",
        "194.87.23.0-194.87.23.255",
        "194.143.140.0-194.143.141.255",
        "194.146.148.0-194.146.151.255",
        "194.146.239.0-194.146.239.255",
        "194.147.164.0-194.147.167.255",
        "194.150.68.0-194.150.71.255",
        "194.156.140.0-194.156.143.255",
        "194.225.0.0-194.225.255.255",
        "195.8.102.0-195.8.102.255",
        "195.8.110.0-195.8.110.255",
        "195.8.112.0-195.8.112.255",
        "195.8.114.0-195.8.114.255",
        "195.20.136.0-195.20.136.255",
        "195.27.14.0-195.27.14.7",
        "195.28.10.0-195.28.11.255",
        "195.28.168.0-195.28.169.255",
        "195.88.188.0-195.88.189.255",
        "195.110.38.0-195.110.39.255",
        "195.114.4.0-195.114.5.255",
        "195.114.8.0-195.114.9.255",
        "195.146.32.0-195.146.63.255",
        "195.170.163.0-195.170.163.255",
        "195.181.0.0-195.181.127.255",
        "195.191.22.0-195.191.23.255",
        "195.191.44.0-195.191.45.255",
        "195.191.74.0-195.191.75.255",
        "195.211.44.0-195.211.47.255",
        "195.219.71.0-195.219.71.255",
        "195.226.223.0-195.226.223.255",
        "195.230.97.0-195.230.97.255",
        "195.230.105.0-195.230.105.255",
        "195.230.107.0-195.230.107.255",
        "195.230.124.0-195.230.124.255",
        "195.234.191.0-195.234.191.255",
        "195.238.231.0-195.238.231.255",
        "195.238.240.0-195.238.240.255",
        "195.238.247.0-195.238.247.255",
        "195.245.70.0-195.245.71.255",
        "196.3.91.0-196.3.91.255",
        "198.46.135.24-198.46.135.31",
        "204.18.0.0-204.18.255.255",
        "204.245.22.24-204.245.22.27",
        "204.245.22.29-204.245.22.31",
        "209.28.123.0-209.28.123.63",
        "210.5.196.64-210.5.196.127",
        "210.5.197.64-210.5.197.127",
        "210.5.198.32-210.5.198.39",
        "210.5.198.64-210.5.198.79",
        "210.5.198.96-210.5.198.223",
        "210.5.204.0-210.5.204.127",
        "210.5.205.0-210.5.205.63",
        "210.5.208.0-210.5.208.63",
        "210.5.208.128-210.5.209.127",
        "210.5.214.192-210.5.214.255",
        "210.5.218.64-210.5.218.255",
        "210.5.232.0-210.5.232.127",
        "210.5.233.0-210.5.233.191",
        "212.1.192.0-212.1.199.255",
        "212.16.64.0-212.16.95.255",
        "212.33.192.0-212.33.223.255",
        "212.80.0.0-212.80.31.255",
        "212.86.64.0-212.86.95.255",
        "212.115.124.1-212.115.127.255",
        "212.120.146.104-212.120.146.111",
        "212.120.146.128-212.120.146.135",
        "212.120.192.0-212.120.223.255",
        "212.151.182.155-212.151.182.157",
        "212.151.186.154-212.151.186.156",
        "213.108.240.0-213.108.243.255",
        "213.109.240.0-213.109.255.255",
        "213.176.0.0-213.176.31.255",
        "213.176.64.0-213.176.127.255",
        "213.195.0.0-213.195.63.255",
        "213.207.192.0-213.207.255.255",
        "213.217.32.0-213.217.63.255",
        "213.232.124.0-213.232.127.255",
        "213.233.160.0-213.233.191.255",
        "217.11.16.0-217.11.31.255",
        "217.24.144.0-217.24.159.255",
        "217.25.48.0-217.25.63.255",
        "217.60.0.0-217.60.255.255",
        "217.66.192.0-217.66.223.255",
        "217.77.112.0-217.77.127.255",
        "217.144.104.0-217.144.107.255",
        "217.146.208.0-217.146.223.255",
        "217.161.16.0-217.161.16.255",
        "217.170.240.0-217.170.255.255",
        "217.171.145.0-217.171.145.255",
        "217.171.148.0-217.171.151.255",
        "217.171.191.220-217.171.191.223",
        "217.172.98.0-217.172.99.255",
        "217.172.102.0-217.172.118.255",
        "217.172.120.0-217.172.127.255",
        "217.174.16.0-217.174.31.255",
        "217.218.0.0-217.219.204.255",
        "217.219.205.64-217.219.255.255",
        );
        $client_ip = adwisedGetUserIpAddr();
        foreach ($ipRanges as $ipRange) {
            $isIranIp = adwised_ip_in_range($client_ip, $ipRange);
            if ($isIranIp) {
                return true;
            }
        }
        return false;
}