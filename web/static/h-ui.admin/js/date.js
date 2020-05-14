function date(dateTime) {
    var date = new Date();
    var year = date.getFullYear();
    var month = date.getMonth()+1;
    var day = date.getDate();
    var hour = date.getHours();
    var minute = date.getMinutes();
    var second = date.getSeconds();
    dateTime = year+'-'+month+'-'+day+ ' ' +hour+':'+minute+':'+second;
    return dateTime;
}