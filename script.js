// var $ = jQuery;
// $(document).ready(function(){

//     $('.wpcf7-form select:not([name=program])').each(function(){

//         $(this).children("option").each(function( i , $obj ){

//             $(this).val(i+1);

//         });

//     });

// });
var jQuery = jQuery;
jQuery(document).ready(function(){

    jQuery('.wpcf7-form select:not([name=program])').each(function(){

        jQuery(this).children("option").each(function( i , jQueryobj ){

            var html = jQuery(this).html();
            var position = html.search("#");

            if( position > -1 ){
                var value = html.slice(position+1, html.length);
                html = html.slice(0, position);
                jQuery(this).html(html);
                jQuery(this).val(value);
            }
            else
                jQuery(this).val(i+1);

        });

    });

});
