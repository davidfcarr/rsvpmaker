export default function save(props) {
	console.log('saveprops',props);
    const {attributes: {youtubelink}} = props;
    let id = '1234';
    console.log('youtube link',youtubelink);
    if(youtubelink) {
        if(youtubelink.indexOf('watch?v=') > 0)
        {
            const match = youtubelink.match(/watch\?v=([^&]+)/);
            if(match && match.length > 1)
                id = match[1];
            console.log('watch= match',match);        
        }
        else {
            //https://youtu.be/Z7KsWatRVOg
            const match = youtubelink.match(/youtu.be\/([^?]+)/);
            if(match && match.length > 1)
                id = match[1];
            console.log('youtu.be match',match);
        }
    }
    const background = {display: 'block', marginLeft: 'auto', marginRight: 'auto', width: '500px', height: '283px', textAlign: 'center', paddingTop: '150px', marginBottom: '-140px', backgroundSize: 'contain', backgroundRepeat: 'no-repeat', textDecoration: 'none', backgroundImage: 'url(https://img.youtube.com/vi/'+id+'/mqdefault.jpg)'};
    const arrow = window.location.origin+'/wp-content/plugins/rsvpmaker/images/youtube-button-100px.png';
	return (			
		<div>
		<a href={youtubelink} style={background}><img style={{objectFit: 'contain', maxWidth: '100%', maxHeight: '100%', opacity: '0.6'}} src={arrow} /></a>
		</div>
	);
}
