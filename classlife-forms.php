<?php
/**
* Plugin Name: Idem Classlife Forms
* Plugin URI: https://github.com/Imrikmar66/classlife-forms
* Description: Link contactform 7 to classlife
* Version: 1.0
* Author: Pierre MAR
* Author URI: https://github.com/Imrikmar66
**/
//add_filter( 'wpcf7_load_js', '__return_false' );

require_once "Bot.php";

add_action( 'wpcf7_submit', 'postClasslife' ); 
add_filter('wpcf7_skip_mail','classlife_skip_mail');

function postClasslife( $contact_form ) {
   $title = $contact_form->title;
   $apikey = get_option('classlife_api_key');
   $apiurl = get_option('classlife_api_url');
   $webhookurl = get_option('classlife_webhook_url');

    if( stripos ($title, "classlife") === FALSE || strlen($apikey) == 0 || strlen($apiurl) == 0 )
       return;

    $submission = WPCF7_Submission::get_instance();
    if ( !$submission )
        return;
    
    $fields = $submission->get_posted_data();
    $fields_unmeta_string = "";
    $fields_meta_string = "";
    $fields_string = "";
    $error = "No error";
    $invalidFields = array();

    if ( $submission->is( 'validation_failed' ) ){
        $status = "validation_failed";
        $message = "Un ou plusieurs champs ont une erreur. Essayez de nouveau";
        $errosFields = $submission->get_invalid_fields();
        foreach ( (array)  $errosFields as $name => $field ) {
            $invalidFields[] = array(
                'into' => 'span.wpcf7-form-control-wrap.'
                    . sanitize_html_class( $name ),
                'message' => $field['reason'],
                'idref' => $field['idref'],
            );
        }
    }
    else {
        $fields['service'] = 'api';
        $fields['apiKey'] = $apikey;
        foreach($fields as $key=>$value) { 
            if(stripos($key, "wpcf7") !== FALSE)
                continue;
            if(stripos($key, "meta-") !== FALSE){
                $key = str_replace("meta-", "meta[", $key);
                $key .= "]";
                $fields_meta_string .= $key.'='.$value.'&'; 
            }
            else
               $fields_unmeta_string .= $key.'='.$value.'&';

            //$fields_string .= $key.'='.$value.'&';
        }
        
        //$fields_string = rtrim($fields_string, '&');
        $fields_unmeta_string = rtrim($fields_unmeta_string, '&');

        //Profile creation

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $apiurl);
        curl_setopt($ch,CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_unmeta_string);
        $result = curl_exec($ch);
        $errors = curl_error($ch);
        curl_close($ch);

        //edit just created profile with received id ... meta fix
        $jsonArr = json_decode($result, true);

        if( 
            $fields['perform'] == 'buildForm'
            && !isset($fields[$fields['model']."_id"])
            && ($id = $jsonArr["id"]) 
        ) {

            $fields_meta_string .= $fields['model']."_id=" . $id . "&";
            $fields_meta_string .= "service=" . $fields['service'] . "&";
            $fields_meta_string .= "apiKey=" . $fields['apiKey'] . "&";
            $fields_meta_string .= "model=" . $fields['model'] . "&";
            $fields_meta_string .= "perform=" . $fields['perform'];

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $apiurl);
            curl_setopt($ch,CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_meta_string);
            $result = curl_exec($ch);
            $errors = curl_error($ch);
            curl_close($ch);

            $distant_response = json_decode($result, true);
        }
        else {
            $distant_response["status"] == "error";
            $distant_response["error"] = "received id is null or was never received";
        }

        if( $distant_response["status"] == "success") {
            $status = "mail_sent";
            $message = "Le formulaire à bien été envoyé !";
        }
        else if ( $distant_response["status"] == "error" ) {
            $status = "mail_failed";
            $error = $distant_response["error"] ? $distant_response["error"] : $distant_response["msg"];
            $message = "Une erreur est survenue lors de l'envoi... Veuillez réessayer plus tard";
        }
        else {
            $status = "mail_failed";
            $message = "Une erreur est survenue lors de l'envoi... Veuillez réessayer plus tard";
        }
    }

    $response = array(
        "into" => '#' . $fields["_wpcf7_unit_tag"],
        "status" => $status,
        "message" => $message,
        "api_error" => $error,
        "curl_errors" => $errors,
        "invalidFields" => $invalidFields,
        "id" => $distant_response["id"]/*,
        "fields" => $fields,
        "fields_str" => $fields_string*/
    );

    echo json_encode($response);

    //Use Slackbot
    if( $status == "mail_sent" && $webhookurl && !empty( $fields['model'] ) ) {
       
        $bot = new Bot( $webhookurl );
        $bot->createMessage();
        $bot->addLine( "Nouveau " . $fields['model'] . " dans Classlife" );

        if( !empty( $fields[ $fields['model']."_lastname" ] ) )
            $bot->addLine( "Nom : " . $fields[ $fields['model']."_lastname" ]);

        if( !empty( $fields[ $fields['model']."_name" ] ) )
            $bot->addLine( "Prénom : " . $fields[ $fields['model']."_name" ]);

        $bot->addLine( "Voir sur classlife : https://lidembeta.classlife.education/admin/" . $fields['model'] . "s" );
        $bot->addLine( "Edition sur classlife : https://lidembeta.classlife.education/admin/" . $fields['model'] . "s/edit/" . $id );
        $bot->send();
    }

    die();

}

function classlife_skip_mail( $contact_form ){
    $title = $contact_form->title;
    if( stripos ($title, "classlife") !== FALSE ){
       return true;
   }
}

/******* SETTINGS ********/
add_action( 'admin_menu', 'classlife_forms_settings_init' );
function classlife_forms_settings_init(){

    add_menu_page( 'ClassLife API', 'Classlife Forms', 'manage_options', 'classlife-forms', 'classlife_forms_init', 'dashicons-admin-generic' );
    add_action( 'admin_init', 'update_classlife_api_key' );
}

function classlife_forms_init(){
    if(isset($_POST["classlife_api_key"]))
        update_option("classlife_api_key", $_POST["classlife_api_key"]);

    if(isset($_POST["classlife_api_url"]))
        update_option("classlife_api_url", $_POST["classlife_api_url"]);

    if(isset($_POST["classlife_webhook_url"]))
        update_option("classlife_webhook_url", $_POST["classlife_webhook_url"]);

    $key = get_option('classlife_api_key');
    $url = get_option('classlife_api_url');
    $hook = get_option('classlife_webhook_url');
?>
    <h1> Edit ClassLife API Key </h1>
    <form  method="post" enctype="multipart/form-data">
        <?php settings_fields( 'classlife_api_key_setting' ); ?>
        <?php do_settings_sections( 'classlife_api_key_setting' ); ?>
        <label>
            <span>ClassLife APIKEY</span>
            <input placeholder="xxxxxxxxxxxxxxxxx" type="text" id="classlife_api_key" name="classlife_api_key" value="<?php echo  $key; ?>" />
        </label>
        <br>
        <?php settings_fields( 'classlife_api_url_setting' ); ?>
        <?php do_settings_sections( 'classlife_api_url_setting' ); ?>
        <label>
            <span>ClassLife API URL</span>
            <input placeholder="http://classlife-api.php" type="text" id="classlife_api_url" name="classlife_api_url" value="<?php echo  $url; ?>" />
        </label>
        <br>
        <?php settings_fields( 'classlife_webhook_url_setting' ); ?>
        <?php do_settings_sections( 'classlife_webhook_url_setting' ); ?>
        <label>
            <span>ClassLife WebHook URL</span>
            <input placeholder="http://classlife-webhook/..." type="text" id="classlife_webhook_url" name="classlife_webhook_url" value="<?php echo  $hook; ?>" />
        </label>
        <?php submit_button('Valider') ?>
    </form>
<?php
}

function update_classlife_api_key(){
    add_option('classlife_api_key', "");
    register_setting( 'classlife_api_key_setting', 'classlife_api_key' );

    add_option('classlife_api_url', "");
    register_setting( 'classlife_api_url_setting', 'classlife_api_url' );

    add_option('classlife_webhook_url', "");
    register_setting( 'classlife_webhook_url_setting', 'classlife_webhook_url' );
}

/****** client side script ******/
function classlife_forms_script() {
	wp_enqueue_script( 'classforms-script', plugin_dir_url( __FILE__ ) . '/script.js', array('jquery'), '1.0');
}

add_action( 'wp_enqueue_scripts', 'classlife_forms_script' );