jQuery(document).ready(function($) {
	$('.timezone_on').click( function () {

		$('.timezone_hint').each( function () {
		
		var utc = $(this).attr('utc');
		var target = $(this).attr('target');
		var localdate = new Date(utc);
		localstring = localdate.toString();
		$('#'+target).html('<div>'+localstring+'<div>');
		var data = {
			'action': 'rsvpmaker_localstring',
			'localstring': localstring
		};
		jQuery.post(ajaxurl, data, function(response) {
		$('#'+target).html('<div>'+response+'</div>');
		});
		
		});
	});

$('.signed_up_ajax').each( function () {

var post = $(this).attr('post');
var data = {
	'action': 'signed_up',
	'event_count': post,
};
jQuery.get(window.location.href, data, function(response) {
$('#signed_up_'+post).html(response);
});

});

});
//end jquery

class RSVPJsonWidget {
    constructor(divid, url, limit, morelink = '') {
        this.el = document.getElementById(divid);
        this.url = url;
        this.limit = limit;
        this.morelink = morelink;
        let eventslist = '';
        //this.showEvent = ;

  fetch(url)
  .then(response => {
    return response.json()
  })
  .then(data => {
    var showmorelink = false;
    if(Array.isArray(data))
        {
        if(limit && (data.length >= limit)) {
            data = data.slice(0,limit);
            showmorelink = true;
        }
        data.forEach(function (value, index, data) {
    if(!value.datetime)
        return '';
    var d = new Date(value.datetime);
    console.log('event '+ index);
    console.log(d);
    eventslist = eventslist.concat('<li><a href="' + value.guid + '">' + value.post_title + ' - ' + value.date + '</a></li>');
    });
        }
    else
        {
            this.el.innerHTML = 'None found: '+data.code;
            console.log(data);
        }
    if(eventslist == '')
       this.el.innerHTML = 'No event listings found';
    else
        {
            if(showmorelink && (morelink != ''))
                eventslist = eventslist.concat('<li><a href="'+morelink+'">More events</a></li>');
            this.el.innerHTML = '<ul class="eventslist rsvpmakerjson">'+eventslist+'</ul>';
        }
  })
  .catch(err => {
    this.el.innerHTML = 'Error fetching events from '+this.url;
    console.log(err);
});

    }
}
