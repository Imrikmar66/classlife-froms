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

add_action( 'wpcf7_submit', 'postClasslife' ); 
add_filter('wpcf7_skip_mail','classlife_skip_mail');

function postClasslife( $contact_form ) {
   $title = $contact_form->title;
   $apikey = get_option('classlife_api_key');

    if( stripos ($title, "classlife") === FALSE || strlen($apikey) == 0 )
       return;

    $submission = WPCF7_Submission::get_instance();
    if ( !$submission )
        return;

    $fields = $submission->get_posted_data();

    $url = 'https://lidembeta.classlife.education/app/apiv1.php';
    $fields['service'] = 'api';
    $fields['apiKey'] = $apikey;
    foreach($fields as $key=>$value) { 
        $fields_string .= $key.'='.$value.'&'; 
    }
    $fields_string = rtrim($fields_string, '&');
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    $result = curl_exec($ch);
    $errors = curl_error($ch);
    curl_close($ch);

    $distant_response = json_decode($result, true);
    $error = "No error";

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

    $response = array(
        "into" => '#' . $fields["_wpcf7_unit_tag"],
        "status" => $status,
        "message" => $message,
        "api_error" => $error,
        "curl_errors" => $errors
    );

    echo json_encode($response);
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
    if(isset($_POST["classlife_api_key"])) {
        update_option("classlife_api_key", $_POST["classlife_api_key"]);
    }
    $key = get_option('classlife_api_key');
?>
    <h1> Edit ClassLife API Key </h1>
    <form  method="post" enctype="multipart/form-data">
        <?php settings_fields( 'classlife_api_key_setting' ); ?>
        <?php do_settings_sections( 'classlife_api_key_setting' ); ?>
        <label>
            <span>ClassLife APIKEY</span>
            <input placeholder="xxxxxxxxxxxxxxxxx" type="text" id="classlife_api_key" name="classlife_api_key" value="<?php echo  $key; ?>" />
        </label>
        <?php submit_button('Valider') ?>
    </form>
<?php
}

function update_classlife_api_key(){
    add_option('classlife_api_key', "");
    register_setting( 'classlife_api_key_setting', 'classlife_api_key' );
}