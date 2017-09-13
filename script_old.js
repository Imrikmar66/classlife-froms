var $ = jQuery;
$(document).ready(function(){

    $('.wpcf7-form input').each(function(){

        var name = $(this).attr('name');
        if(name && ( name.indexOf("meta-") > -1) ) {

            name = name.replace("meta-", "meta[");
            name += "]";
            $(this).attr('name', name);
        }

    });

});