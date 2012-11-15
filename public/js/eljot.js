var hoverFix = function(){
    $('a, span').hover(function() {
        $(this).addClass('hover');
    }, function() {
        $(this).removeClass('hover');
    });
};

var attachDatepickers = function(query, options){
    var elements = $(query);
    if(elements.length){
       options = options || {};
       $(elements).datepicker(options);
    }
};

var setSelectAutosubmision = function(query, options){
    var elements = $(query);
    if(elements.length){
       options = options || {};
       elements.bind('change', function(element){
           var form = $(element.currentTarget).parents('form');
           if(form.length){
               form.submit();
           }
       })
    }
}

var setSelectClear = function(buttonQuery, selectQuery, options){
    var buttonElements = $(buttonQuery);
    var selectElements = $(selectQuery);
    
    if(selectElements.length){
        options = options || {};
        
        if(buttonElements.length){
            buttonElements.bind('click', function(element){
                var target = $(element.currentTarget);
                
                var text1 = "";
                selectElements.children("option").filter(function(index, element) {
                    return $(element).text() == text1; 
                }).attr('selected', true);
            });
        }
    }
}

blockUI = function(query){
    $(query).block({ 
        message: '<span class="busy">Odświeżanie...</span>', 
        css: { 
                width: '80%', 
                'font-size': '18px',
                border: 'none', 
                padding: '5px', 
                backgroundColor: '#000', 
                '-webkit-border-radius': '10px', 
                '-moz-border-radius': '10px', 
                opacity: .6, 
                color: '#fff' 
            } 
    });
};

unblockUI = function(query){
    $(query).unblock();
}

var requestOrdersReceivedTomorrow = function(){
    blockUI('div.ajax-content > div');
    $.ajax({
        type: "POST",
        url: "/order/orders-received-tomorrow?format=json",
        dataType: "html"
    }).success(function(data, textStatus, request) {
        if(textStatus == "success"){
           var $container = $('div.ajax-content');
           if($container.length == 0){
                $container = $('<div class="ajax-content"></div>');
                $container.html(data);
                $('#navigation').after($container);
           } else {
                $container.html(data);
           }
        }
    }).complete(function(){
        unblockUI('div.ajax-content > div');
    });
}

var setRequestInterval = function(period){
    period = period || 10000;
    requestOrdersReceivedTomorrow();
    var tid = setInterval(requestOrdersReceivedTomorrow, period);
//    function abortTimer() {
//        clearInterval(tid);
//    }
}


$(function(){
//    var date = new Date();
//    date = date.getFullYear() + "-" + date.get() + "-" + date.getDay();
    hoverFix();
    attachDatepickers("input#date_of_receipt", {"dateFormat": "yy-mm-dd"});
    attachDatepickers("input#date_of_payment", {"dateFormat": "yy-mm-dd"});
//    setSelectAutosubmision("select[name=order_year]");
    
    setSelectClear("button#filter-order_year", "select#export");
    setSelectClear("button#search-button", "select#export");
    setRequestInterval();
});