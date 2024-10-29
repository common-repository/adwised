<style>
    #adwised_save_setting{
        background: #041b33;
        padding: 5px 40px;
        box-shadow: 0 2px 10px rgba(255, 213, 0, 0.3);
        color: #ffc700;
        font-weight: 900;
    }

    #pop_up_error{
        color: #fff;
        text-align: center;
        padding: 5px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: none;
    }

    #wpfooter{
        position: relative;
    }

    .postbox {
        margin: 10px 0 40px;
		padding: 0 0 40px;
	}

        .postbox > div {
            padding: 20px 20px 0 20px;
        }

        .postbox .label-container,
	    .postbox label {
            font-weight: 600;
            margin: 0 0 10px;   
			line-height: 20px;
		}
	
		.postbox .label-container {
            float: left;
            width: 100px;            
		}

        .postbox h3 {
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
            font-size: 18px !important;
            margin: 0 0 20px !important;
	    }

        .postbox input[type="text"] {
            direction: ltr;
            text-align: left;
            font-family:Courier New;
            max-width: 800px;
	    }

    .pop_btn_wrapper {
        text-align: center;
    }

        .pop_btn_wrapper button {
            padding: 10px;
            background: #ffc702;
            color: #051b32;
            font-weight: 400;
            border: navajowhite;
            box-shadow: 0 2px 10px rgba(255, 199, 2, 0.5);
            border-radius: 5px;
            cursor: pointer;
        }

    form input[type="submit"] {
        text-align: center;
	}
</style>

<div class="wrap">
    <h2><?php echo $this->plugin->displayName; ?></h2>

    <?php
        if (isset($this->message)) 
        {
    ?>
            <div class="updated fade"><p><?php echo $this->message; ?></p></div>
    <?php
        }
        if (isset($this->errorMessage)) 
        {
    ?>
            <div class="error fade"><p><?php echo $this->errorMessage; ?></p></div>
    <?php
        }
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <div class="">
                        <?php
                            $args = array('public' => true,);
                            $list_cpt = get_post_types($args,"object");
                            $adwised_cpt_select = get_option("adwised_cpt_select");
                        ?>
                        <div class="inside">
                            <form id="adwised_setting_form" action="options-general.php?page=<?php echo $this->plugin->name; ?>" method="post">
                                <div class="postbox">
                                    <h3>تنظیمات کلی</h3>
                                    <div>
                                        <div class="label-container">
                                            <label for="adwisedPopSecretkey"><?php esc_html_e( 'سکرت کی', 'adwised' ); ?></label>
                                        </div>
                                        <input type="text" name="adwisedPopSecretkey" id="adwisedPopSecretkey" class="widefat" value="<?php echo get_option('adwisedPopSecretkey'); ?>" />
                                    </div>
                                </div>
                                <div class="postbox">
                                    <h3>تنظیمات وب پوش</h3>
                                    <div>
                                        <input type="checkbox" name="adwisedPushDeactivate" id="adwisedPushDeactivate" value="1" <?php if(get_option('adwisedPushDeactivate')):echo "checked";endif; ?> />
                                        <label for="adwisedPushDeactivate"><?php esc_html_e( 'غیر فعال کردن پوش (ارسال پوش و دریافت توکن)', 'adwised' ); ?></label>
                                    </div>
                                    <div>
                                        <div class="label-container">
                                            <label for="adwised_client_id"><?php esc_html_e( 'کلاینت ای دی', 'adwised' ); ?></label>
                                        </div>
                                        <input type="text" name="adwised_client_id" id="adwised_client_id" class="widefat" value="<?php echo get_option("adwised_client_id"); ?>" />
                                    </div>
                                    <div>
                                        <div class="label-container">
                                            <label for="adwised_topic"><?php esc_html_e( 'تاپیک', 'adwised' ); ?></label>
                                        </div>
                                        <input type="text" name="adwised_topic" id="adwised_topic" class="widefat" value="<?php echo get_option('adwised_topic'); ?>" />
                                    </div>
                                </div>
                                <div class="postbox">
                                    <h3>تنظیمات پاپ آپ</h3>
                                    <div>
                                        <input type="checkbox" name="adwisedPopDeactivate" id="adwisedPopDeactivate" <?php if(get_option('adwisedPopDeactivate')):echo "checked";endif; ?> value="1" />
                                        <label for="adwisedPopDeactivate"><?php esc_html_e( 'غیر فعال کردن پاپ آپ', 'adwised' ); ?></label>
                                    </div>
                                    <div>
                                        <div class="label-container">
                                            <label for="adwisedPopId"><?php esc_html_e( 'پاپ ای دی', 'adwised' ); ?></label>
                                        </div>
                                        <input type="text" name="adwisedPopId" id="adwisedPopId" class="widefat" value="<?php echo get_option('adwisedPopId'); ?>" />
                                    </div>
                                    <?php
                                        if (!get_option('adwisedPopConfig')->pop_url){
                                            ?>
                                            <p><span style="color: red; font-size: 14px; font-weight: bold">توجه:</span>
                                                لطفا جهت فعال سازی پاپ آپ بعد از وارد کردن پاپ آی دی و سکرت کی بر روی دکمه زیر کلیک کنید(فقط برای بار اول نیاز است)</p>
                                            <p class="pop_btn_wrapper"><button id="get_pop_url_btn">دریافت لینک تبلیغ پاپ</button></p>
                                            <p id="pop_up_error" style="display: none"></p>
                                            <?php
                                        }
                                    ?>
                                </div>
                                <div class="postbox">
                                    <h3>تنظیمات iframe</h3>
                                    <div>
                                        <input type="checkbox" name="adwisedIframeDeactivate" id="adwisedIframeDeactivate" <?php if(get_option('adwisedIframeDeactivate')):echo "checked";endif; ?> value="1" />
                                        <label for="adwisedIframeDeactivate"><?php esc_html_e( 'غیر فعال کردن آی فریم', 'adwised' ); ?></label>
                                    </div>
                                   
                                   
                                </div>
                                <?php wp_nonce_field( $this->plugin->name, $this->plugin->name . '_nonce' ); ?>
                                <p style="text-align: center">
                                    <input type="submit" name="submit" name="Submit" id="adwised_save_setting" class="button button-primary" value="<?php esc_html_e('ذخیره', 'adwised'); ?>" />
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sidebar -->
        <?php
            //for making sidebar disable
            $disable=false;
            if ($disable){
                ?>
                <div id="postbox-container-1" class="postbox-container">
                    <?php require_once( $this->plugin->folder . '/views/sidebar.php' ); ?>
                </div>
                <?php
            }
        ?>
    </div>
</div>
