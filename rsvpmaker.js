jQuery(document).ready(function($) {

    $.ajaxSetup({

        headers: {

            'X-WP-Nonce': rsvpmaker_rest.nonce,

        }

    });

    $('.timezone_on').click( function () {
    	var utc = $(this).attr('utc');
        var target = $(this).attr('target');
        var newtz = target.replace('timezone_converted','tz_convert_to');
        var event_tz  = $(this).attr('event_tz');
        if(event_tz == '')
        {
            console.log('tz convert already ran');
            return;
        }
		var localdate = new Date(utc);

		localstring = localdate.toString();

        $('#'+target).html(localstring);
        var match = localstring.match(/\(([^)]+)/);
        $(this).attr('event_tz','');//so it won't run twice
        $('#'+newtz).html('Converting to '+match[1]);
        var timeparts = utc.split(/T/);
        var newtime;
        var timecount = 0;
        $('.tz-convert, .tz-convert table tr td, .tz-table1 table tr td:first-child, .tz-table2 table tr td:nth-child(2), .tz-table3 table tr td:nth-child(3)').each(
            function () {
            celltime = this.innerHTML.replace('&nbsp;',' ');
            //if contains time but not more html
            if((celltime.search(/\d:\d\d/) >= 0) && (celltime.search('<') < 0)) {
            timecount++;
            newtime = timeparts[0]+' '+celltime+' '+event_tz;
            ts = Date.parse(newtime);
            if(!Number.isNaN(ts))
                {
                localdate.setTime(ts);
                newtime = localdate.toLocaleTimeString().replace(':00 ',' ');
                this.innerHTML = newtime;
                $(this).css('font-weight','bold');
                }
            }            
            
            });

        var checkrow = true;
        $('.tz-table1 table tr td:first-child, .tz-table2 table tr td:nth-child(2), .tz-table3 table tr td:nth-child(3)').each( 
            function() {
                if(checkrow && (this.innerHTML != '') && (this.innerHTML.search(':') < 0) ) // if this looks like a column header
                    this.innerHTML = '<strong>Your TZ</strong>';
                checkrow = false;
            }
        );
        
            var data = {

                'action': 'rsvpmaker_localstring',
    
                'localstring': localstring
    
            };
    
            jQuery.post(ajaxurl, data, function(response) {

		$('#'+target).html(response);

		});

});


$('.signed_up_ajax').each( function () {



var post = $(this).attr('post');

var data = {

	'event': post,

};

jQuery.get(rsvpmaker_rest.rest_url+'rsvpmaker/v1/signed_up', data, function(response) {

$('#signed_up_'+post).html(response);

});



});



var guestlist = '';



function format_guestlist(guest) {

if(!guest.first)

    return;

guestlist = guestlist.concat('<h3>'+guest.first);

if(guest.last)

    guestlist = guestlist.concat(' '+guest.last);

guestlist = guestlist.concat('</h3>\n');

if(guest.note)

    guestlist = guestlist.concat('<p>'+guest.note+'</p>');

}



function display_guestlist (post_id) {

    var url = rsvpmaker_json_url+'guestlist/'+post_id;

    fetch(url)

    .then(response => {

      return response.json()

    })

    .then(data => {

        if(Array.isArray(data))

        {

            data.forEach(format_guestlist);

            if(guestlist == '')

                guestlist = '<div>?</div>';

            $('#attendees-'+post_id).html(guestlist);

        }

    })

    .catch(err => {

        console.log(err);

        $('#attendees-'+post_id).html('Error fetching guestlist from '+url);

  });

  

}



$( ".rsvpmaker_show_attendees" ).click(function( event ) {

    var post_id = $(this).attr('post_id');

    guestlist = '';

    display_guestlist(post_id);//,nonce);

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

    eventslist = eventslist.concat('<li><a href="' + value.guid + '">' + value.post_title + ' - ' + value.date + '</a></li>');

    });

        }

    else

        {

            this.el.innerHTML = 'None found: '+data.code;

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

