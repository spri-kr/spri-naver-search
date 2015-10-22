// varialble for keeping news articles
var spri_articles;

// All ajax functions are located at spri-naver-search-option.php
jQuery(document).ready(function ($) {

    // getting news articles for  selected query via ajax
    jQuery("#get_article").click(function () {

        //prepare query id for sending
        var query_id = jQuery("select[name=query]").val();

        var ajax_option = {
            url: "/wp-admin/admin-ajax.php",
            method: "POST",
            data: {
                action: "spri_naver_get_article_list",
                query_id: query_id
            },
            success: get_articles
        };

        // empty article list space and show loading message
        jQuery(".article-list").empty().html("Loading!");

        //send ajax query
        jQuery.ajax(ajax_option);
    });

    // callback function after ajax request
    function get_articles(r) {
        // result is json format
        spri_articles = JSON.parse(r);

        //clear article list space
        jQuery(".article-list").empty();

        // Display result
        if (spri_articles.length == 0) {
            jQuery(".article-list").html("No Result!");
        }
        else {
            // display result articles number
            jQuery(".article-list").append(spri_articles.length + " Results!");

            // transform json object into html element vir template
            // template is located at 'spri_naver_option::article_dashboard'
            spri_articles.forEach(function (article, i) {
                var item;
                item = ich.article_template(article);
                if (i % 4 == 0) {
                    div = document.createElement('div');
                    div.className = "row";
                    jQuery(".article-list").append(div);
                }
                jQuery(div).append(item);
            });

            // add on, off slide buttons
            jQuery(".item input[value=false]").removeProp('checked');
            jQuery(".item input[type=checkbox]").bootstrapSwitch(
                {
                    size: "mini",
                    offColor: "danger",
                    onSwitchChange: update_display_option
                });

        }

    }

    // change articles display on, off option via slide button
    function update_display_option(r) {
        var p_id = jQuery(this).attr("name");
        var display_val = jQuery(this).bootstrapSwitch('state');
        var ajax_option = {
            url: "/wp-admin/admin-ajax.php",
            method: "POST",
            data: {
                action: "spri_naver_update_display",
                p_id: p_id,
                display_val: display_val
            },
            success: function (r) {
                console.log(r);
            }
        };

        jQuery.ajax(ajax_option);
    }

});
