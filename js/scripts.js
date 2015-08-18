var spri_articles;

jQuery(document).ready(function ($) {
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

        // empty and show loading message
        jQuery(".article-list").empty().html("Loading!");

        //Tada!
        jQuery.ajax(ajax_option);
    });

    function get_articles(r) {
        spri_articles = JSON.parse(r);
        jQuery(".article-list").empty();

        if (spri_articles.length == 0) {
            jQuery(".article-list").html("No Result!");
        }
        else {
            jQuery(".article-list").append(spri_articles.length + " Results!");

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

            jQuery(".item input[value=false]").removeProp('checked');

            jQuery(".item input[type=checkbox]").bootstrapSwitch(
                {
                    size: "mini",
                    offColor: "danger",
                    onSwitchChange: update_display
                });

        }

    }

    function update_display(r) {
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
