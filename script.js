var $ = jQuery;
$(document).ready(function(){

    $('.wpcf7-form select:not([name=program])').each(function(){

        $(this).children("option").each(function( i , $obj ){

            $(this).val(i+1);

        });

    });

});