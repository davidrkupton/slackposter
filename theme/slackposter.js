var ButtonCount=1;      //keeps track of the button id if multiple buttons created
var timeout=null;
/*
    These functions create buttons to launch various types of slack input screens.
    returns the button id.
    the button is appended to the anchor supplied and is themeable, deployed in a <div> slackLaunchWrapper and
        the <input type=button> has the id as returned and a class slackLaunch
 */
function tm_slack_add_support_button(parent, buttonTxt, note, value){
    //so first make the basic button
    value = typeof value !== 'undefined' ? value : null;
    var user="Support Request ("+tm_slack.user+")";
    var channel=null;
    var ret=tm_slack_add_button(parent, buttonTxt, channel, user, note);
    // and now rebind the button to a new function
    $(ret).attr({"tms-default":value}).unbind("click touchstart").bind("click touchstart", function(){
        tm_slack_support_popup($(this).attr("tms-channel"), $(this).attr("tms-user"), $(this).attr("tms-note"), $(this).attr("tms-default"));
        $("#slackFade, #slackPop").fadeIn(1000);
        $(this).effect("transfer",{to:$("#slackPop")},800);
    });
    $('.slackLaunchWrapper').attr({"title":"Make Support Request"});
    return ret;
}
function tm_slack_add_button(parent, buttonTxt, channel, user, note){
    channel = typeof channel !== 'undefined' ? channel : null;
    user = typeof user !== 'undefined' ? user : null;
    var id="slackButton"+ButtonCount;
    $(parent).append($("<div id='"+id+"Wrapper"+ButtonCount+"'>").addClass('slackLaunchWrapper').attr({"title":"Post to Slack"})
            .append($("<input type='submit' id='"+id+"'>").addClass("slackLaunch").attr("value",buttonTxt))
    );
    $("#"+id).attr({"tms-channel":channel,"tms-user":user,"tms-note":note}).bind("click touchstart", function(){
        tm_slack_popup($(this).attr("tms-channel"), $(this).attr("tms-user"), $(this).attr("tms-note"));
        $("#slackFade, #slackPop").fadeIn(1000);
        $(this).effect("transfer",{to:$("#slackPop")},800);
    });
    ButtonCount++;
    return "#"+id;          //return the id of this button
}

function tm_slack_add_support_anchor(parent, buttonTxt, note, value){
    //so first make the basic button
    value = typeof value !== 'undefined' ? value : null;
    var user="Support Request ("+tm_slack.user+")";
    var channel=null;
    var ret=tm_slack_add_anchor(parent, buttonTxt, channel, user, note);
    // and now rebind the button to a new function
    $(ret).attr({"tms-default":value}).unbind("click touchstart").bind("click touchstart", function(){
        tm_slack_support_popup($(this).attr("tms-channel"), $(this).attr("tms-user"), $(this).attr("tms-note"), $(this).attr("tms-default"));
        $("#slackFade, #slackPop").fadeIn(1000);
        $(this).effect("transfer",{to:$("#slackPop")},800);
    });
    $('.slackLaunchWrapper').attr({"title":"Make Support Request"});
    return ret;
}
function tm_slack_add_anchor(parent, buttonTxt, channel, user, note){
    channel = typeof channel !== 'undefined' ? channel : null;
    user = typeof user !== 'undefined' ? user : null;
    var id="slackButton"+ButtonCount;
    $(parent).append($("<div id='"+id+"Wrapper"+ButtonCount+"'>").addClass('slackLaunchWrapper').attr({"title":"Post to Slack"})
            .append($("<a id='"+id+"'>").addClass("slackLaunch").html("<i class='fa fa-support'>&nbsp;</i>"+buttonTxt))
    );
    $("#"+id).attr({"tms-channel":channel,"tms-user":user,"tms-note":note}).bind("click touchstart", function(){
        tm_slack_popup($(this).attr("tms-channel"), $(this).attr("tms-user"), $(this).attr("tms-note"));
        $("#slackFade, #slackPop").fadeIn(1000);
        $(this).effect("transfer",{to:$("#slackPop")},800);
    });
    ButtonCount++;
    return "#"+id;          //return the id of this button
}

/*
 * If channel and user are null or not supplied, then the defaults for the channel will be used and user will not be able to change them
 * if channel and user are empty strings, then the user will be able to specify them
 * if channel and user are supplied then they will be used and user will not be able to change them
*/
function tm_slack_support_popup(channel, user, note, value){
    // first make the usual popup
    tm_slack_popup(channel, user, note);  // its still not displaying
    // make an array of products for the select
    var products={};
    products["--Select Product--"] = 0;
    products["InterpreTA"] = "InterpreTA";
    products["Market Monitor"] = "Market Monitor";
    products["Maverick"] = "Maverick";
    products["Research"] = "TradermadeWeb Research";
    products["TraderMade404"] = "TraderMade404";
    products["Other/General"] = "Other";
    if (value==null || typeof value===undefined) value=0;
    // now add some bits and pieces related to support only
    $("#narrative").append($("<div id='suppText'>").html("Support provided between<br>7am and 6pm local time<br>UK business working days."));
    $("#commentWrapper")  .before($("<div id='productWrapper'>").addClass("slack-form-wrapper")
            .append($("<label>").html("Product:"))
            .append($("<select id='product'>").addClass("slack-form-item"))
    );
    $.each(products, function(key, value) {
        $('#product').append($("<option/>", {value: value, text: key }));
    });
    $('#product').val(value);

    $("#commentWrapper")  .before($("<div id='subjectWrapper'>").addClass("slack-form-wrapper")
            .append($("<label>").html("Subject:"))
            .append($("<textarea id='subject'>").addClass("slack-form-item"))
    );

    $("#commentWrapper label").html("Issue:");
    $('#slack_submit').attr("value",'Submit');

    //reposition
    tms_popup_locate("#slackPop");

    // create the submit functionality
    $("#slack_submit").unbind("click touchstart").bind("click touchstart",function(){
        // post to slack
        if(validate("support")){
            var post=   "User: "+tm_slack.user+
                        "\nUser Email: "+tm_slack.email+
                        "\nProduct: "+$("#slackPop #product").val()+
                        "\n---\nSubject: "+$("#slackPop #subject").val()+
                        "\nDescription: "+$("#slackPop #comment").val()+
                        "\n---\nLocation: "+document.title+" \n<"+document.location.toString()+">";
           // tm_slack_post(post,$("#slackPop #channel").val(),$("#slackPop #user").val());
            tm_slack_post(post,tm_slack.support,$("#slackPop #user").val());
            // post to salesforce
            if(tm_slack.sf) {
                var payload = {};
                payload.subject = $("#slackPop #subject").val();
                payload.description = $("#slackPop #comment").val() + " \n<page:" + document.location.toString() + ">\n" + document.title;
                payload.product = $("#slackPop #product").val();
                payload.email = tm_slack.email;
                payload.name = tm_slack.user;
                sf_post(payload);
            }
        }
    });

    //set the focus...
    $('#product').focus();
}
function tm_slack_popup(channel, user, note){
    // create the popup framework
    $("body").append($("<div id=slackFade>"));
    $("body").append($("<div id=slackPop>").append($("<div id='narrative'>").html(note)));

    if(channel!="" && channel!=null) $("#slackPop").append($("<input type='hidden' id='channel'>").val(channel));
    else if(channel=="") $("#slackPop").append($("<div id='channelWrapper'>").addClass("slack-form-wrapper")
            .append($("<label>").html("Channel:"))
            .append($("<input type='text' id='channel'>").addClass("slack-form-item"))
    );      // if channel is null, then this wont show and the default for this webhook will be used
            // if channel is provided, then that value is used but hidden

    if(user!="" && user!=null) $("#slackPop").append($("<input type='hidden' id='user'>").val(user));
    else if(user=="") $("#slackPop").append($("<div id='userWrapper'>").addClass("slack-form-wrapper")
            .append($("<label>").html("Post Author:"))
            .append($("<input type='text' id='user'>").addClass("slack-form-item"))
    );  // if no user is provided, then this wont show and the default for this module (set in SlackPost()) will be used

    //Make the popup
    $("#slackPop")  .append($("<div id='commentWrapper'>").addClass("slack-form-wrapper")
                .append($("<label>").html("Posting:"))
                .append($("<textarea id='comment'>").addClass("slack-form-item"))
            )
        .append($("<input type='button' id='slack_submit' value='Post'>").addClass("slack-form-button"))
        .append($("<input type='button' id='slack_cancel' value='Cancel'>").addClass("slack-form-button"))
        .append($("<div id='slack_message'>"));

    // size the fade area
    $("#slackFade").css("height",$(document).height() + "px")

    //position the form
    tms_popup_locate("#slackPop");

    // create the input areas

    // create the submit functionality
    $("#slack_submit").bind("click touchstart",function(){
        if(validate()) {
            $("#slack_submit, #slack_cancel").prop("disabled", true);
            $("#slack_message").html("<i class='fa fa-spinner fa-pulse'></i> Posting...").show();
            var comment="User: "+tm_slack.user+
                "\nUser Email: "+tm_slack.email+
                "\nComment: "+$("#slackPop #comment").val()+
                "\n---\nLocation: "+document.title+" \n<"+document.location.toString()+">";
            tm_slack_post(comment, $("#slackPop #channel").val(), $("#slackPop #user").val());
        }
    });
    $("#slack_cancel").bind("click touchstart",function(){
        $("#slackFade").fadeOut(250);
        $("#slackPop").effect("transfer",{to:$("#slackButton1")},1000, function(){$("#slackFade, #slackPop").remove();})
            .dequeue()
            .fadeOut(800);
    });

    // set the focus...
    $("#comment").focus();
}

function validate(type){
    $(".tms-failed").removeClass("tms-failed");
    $(".err").removeClass("err");
    $("#slack-message").html("").hide();
    var result=true;
    if($("#slackPop #comment").val().length<5){
        $("#slackPop #comment").addClass("tms-failed");
        result=false;
    }
    if (type=="support"){
        if($("#slackPop #subject").val().length<5){
            $("#slackPop #subject").addClass("tms-failed");
            result=false;
        }
        if($("#slackPop #product").val()==0){
            $("#slackPop #product").addClass("tms-failed");
            result=false;
        }
    }
    if(!result) $("#slack_message").html("Require highlighted information.").addClass("err").show();
    return result;
}

function tms_popup_locate(div){
    var top=($(window).height()-$(div).height())/4;
    var left=($(window).width()-$(div).width())/2;
    $(div).css({"top":top,"left":left});
}
/*
    post the comment/whatever to slack webhook using Ajax
 */
function tm_slack_post(comment, channel, user){

    // if channel is not provided, then it will post to the default set in Slack for this webhook
    user = typeof user !== 'undefined' ? user : 'Website User';        // this is the default user

    var text ={};
    text['text'] = comment;
    text['icon_url'] = tm_slack.url + "/images/mavround.gif";
    text['username'] = user;
    if(channel) text['channel'] = channel;

    $.ajax({
        data: 'payload=' + encodeURIComponent(JSON.stringify(text)),
        type:"POST",
        url: "/sites/all/modules/tm_slack/tm_slack.proxy.php",
        success: function(data){
            if(data.toLowerCase()=="ok") {
                $("#slack_message").fadeOut(function(){$(this).html("<i class='fa fa-check'></i>&nbsp;Posting Success").addClass("ok").show();});
                if(timeout) clearTimeout(timeout);
                timeout=setTimeout(function(){
                    $("#slackFade").fadeOut(250);
                    $("#slackPop").effect("transfer",{to:$("#slackButton1")},1000, function(){$("#slackFade, #slackPop").remove();})
                        .dequeue()
                        .fadeOut(800);
                },5000);
            } else{
                console.log("Slack Error: Unexpected response - "+data);
                if(timeout) clearTimeout(timeout);
                $("#slack_message").fadeOut(function(){$(this).html("<i class='fa fa-times'></i>&nbsp;Posting Error. "+data).addClass("err").show();});
            }
            $("#slack_submit,#slack_cancel").prop("disabled", false);
        },
        error:function(error){
            console.log("Slack Error: Ajax fail - "+error);
            $("#slack_submit,#slack_cancel").prop("disabled", false);
            if(timeout) clearTimeout(timeout);
            $("#slack_message").html("<i class='fa fa-times'></i>&nbsp;Posting Error. "+error.responseText).addClass("err");
        }

    });
}
/* post to salesforce using slack */
function sf_post(payload){
    if(!tm_slack.sf) return true;
    payload["sf_destination"]="case";
    $.ajax({
        data: 'payload=' + encodeURIComponent(JSON.stringify(payload)),
        type:"POST",
        url: "/sites/all/modules/tm_utilities/includes/salesforce_poster/sf-form-submitter.php",
        success: function(data){
            if(isset(data) && data!="") data=JSON.parse(data);
            if(data.result.toLowerCase()=="ok") {
                $("#slack_message").html("<i class='fa fa-check'></i>&nbsp;Posting Success").addClass("ok");
                if(timeout) clearTimeout(timeout);
                timeout=setTimeout(function(){
                    $("#slackFade, #slackPop").fadeOut(function () {
                        $("#slackFade, #slackPop").remove();
                    });
                },1500);
            }
            else{
                console.log("Salesforce Error: Unexpected response - "+data);
                if(timeout) clearTimeout(timeout);
                $("#slack_message").html("<i class='fa fa-times'></i>&nbsp;Posting Error. "+data.result).addClass("err");
            }
        },
        error:function(error){
            console.log("Salesforce Error: Ajax fail - "+error);
            if(timeout) clearTimeout(timeout);
            $("#slack_message").html("<i class='fa fa-times'></i>&nbsp;Posting Error. "+error.responseText).addClass("err");
        }

    });
}