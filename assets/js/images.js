var Images = React.createClass({
	getInitialState: function() {
		return {
			start: parseInt(args.start),
			limit: parseInt(args.limit),
			item: []
		};
	},

	handleClick: function(item,e) {
		this.setState({
			item: [item]
		});
	},

	componentDidUpdate: function(prevProps,prevState) {
		if(prevState.item != this.state.item) {
			jQuery('#fbc_media-sidebar').animate({"right":"0px"}, "fast").show();
			jQuery('#__attachments-view-fbc').css({'margin-right':'300px' });
		}
		React.render(<Attachment attachment={this.state.item} />, document.getElementById('fbc_media-sidebar') );

		jQuery('.fbc_attachment').each(function(){
			jQuery(this).css('opacity',1);
		});
	},

    render: function() {

		if( this.state.start > 0 )
			var addMore = this.state.start;

        return (
			<span>
				{ this.props.data.map(function(item, i) {

					var divStyle = {
						backgroundImage: 'url(' + item[0].img + ')',
					};

					jQuery('.fbc_attachment').each(function(){
						jQuery(this).css('opacity',1);
					});

					return (
						<li className="fbc_attachment attachment" onClick={this.handleClick.bind(this,item[0])}>
			                <div className="attachment-preview" style={divStyle}>
								<a href={item[0].img} className="fullscreen" data-featherlight="image">
									<i className="icon-resize"></i>
								</a>
			                </div>
			            </li>
					);
				}, this)}
			</span>
        );
    }

});

var FlightImages = React.createClass({
	getInitialState: function() {
		return {
			src: this.props.path,
			album: {name: 'Recent Images'},
			search: this.props.search,
			//start: parseInt(args.start),
			//limit: parseInt(args.limit),
			start: 0,
			limit: 30,
			data: [],
			filter: this.props.filter,
			type: this.props.type,
			processingCount: 0
		};
	},

	loadMore: function(e) {
		jQuery('#loader').show();
		this.setState({
			start: this.state.start+this.state.limit,
			src: args.FBC_URL +"/includes/lib/get.php?subdomain="+ args.subdomain +"&token="+ args.token +"&album="+ this.state.album.id +"&limit="+ this.state.limit +"&start="+ (this.state.start+this.state.limit)
		});
	},

	repeat: function(item,cnt,length,found, targetUrl) {
		var self = this;
		if (this.state.src == targetUrl) {
			if (this.props.filter == 'all' || this.props.filter == '' || this.props.filter == item.scheme) {
	        	// do filter work, only show sepcified scheme
		    $.ajax({
		            url: args.FBC_URL +"/includes/lib/download.php?id="+ item.id +"&subdomain="+ args.subdomain +"&token="+ args.token +"&limit="+ this.state.limit +"&start="+ this.state.start
				})
				.done(function(e) {


					if (self.state.src == targetUrl) {
						var start = e.search('Location: .*[\r\n]');
						var startStr = e.substring(start);
						var stop = startStr.search('[\r\n]');
						var imgFile = startStr.substring( 10 ,stop);

						var expires = imgFile.split('?X-Amz-Security-Token');
						var img = expires[0];

						var fileExt = img.split('.').pop();
						var ext = fileExt.split('%');
						ext[0] = ext[0].toLowerCase();

						//if(ext[0] == "jpg" || ext[0] == "jpeg" || ext[0] == "gif" || ext[0] == "png" || ext[0] == "pdf") {
							var image = [{
									"id": item.id,
									"scheme": item.scheme,
									"name": item.name,
									"owner": item.owner,
									"ownerName": item.ownerName,
									"size": item.size,
									"time": item.time,
									"img": imgFile,
									"description": item.description,
									//"copyright": result.copyright,
									//"terms": result.termsAndConditions
							}];

							var arr = self.state.data.slice();
							arr.push(image);
							self.setState({data: arr});
						//}

						var currentCount = self.state.processingCount + 1;
						self.setState({processingCount: currentCount});
						if(currentCount == length) {
							jQuery('#loader').hide();

							if(found > (self.state.start+self.state.limit))
								jQuery('#fbc_loadMore').show();
							else
								jQuery('#fbc_loadMore').hide();
						}
					}




				})
				.always(function() {
				});
			} else {
				var currentCount = this.state.processingCount + 1;
				this.setState({processingCount: currentCount});
				if(currentCount == length) {
					jQuery('#loader').hide();

					if(found > (self.state.start+self.state.limit))
						jQuery('#fbc_loadMore').show();
					else
						jQuery('#fbc_loadMore').hide();
				}
	  	}
		}
	},

	componentDidMount: function() {
		if(args.token == '') {
			jQuery('#loader').hide();
			jQuery("#fbc-react").html("<h2 style='font-size: 16px; margin: 70px 370px 0 40px; color:#222;'>Sorry, but authentication failed. Please visit plugin settings to login.</h2>");
		} else {
			jQuery('#loader').show();
			var self = this;
			$.ajax({
				url: this.state.src,
				dataType: 'json',
				cache: false
			})
			.done(function(data) {
				var cnt = 1;
				if (data.results != null) {
		            $.each(data.results, function(k,v) {
		                self.repeat(v,cnt,data.results.length,data.found, self.state.src);
						cnt++;
		            });
				} else {
					jQuery('#loader').hide();
					jQuery('#fbc_loadMore').hide();
				}
			});
		}
	},

	componentWillReceiveProps: function(nextProps) {
		this.setState({
			type: nextProps.type
		});
		if(nextProps.type == 'library' && nextProps.album.id != this.state.album.id) {
			this.setState({
				album: nextProps.album,
				start: 0,
				data: [],
				filter: nextProps.filter,
				src: args.FBC_URL +"/includes/lib/get.php?subdomain="+ args.subdomain +"&album="+ nextProps.album.id +"&token="+ args.token +"&limit="+ this.state.limit +"&start=0"
			});
		}
		if(nextProps.type == 'search' && nextProps.search != this.state.search) {
			this.setState({
				album: {
					name: 'Search Results: '+nextProps.search
				},
				search: nextProps.search,
				start: 0,
				data: [],
				filter: nextProps.filter,
				src: args.FBC_URL +"/includes/lib/get.php?subdomain="+ args.subdomain +"&keyword="+ nextProps.search.replace(" ","%2B") +"&token="+ args.token +"&limit=100&start=0"
			});
		}
		if(nextProps.type == 'filter' && nextProps.filter != this.state.filter) {
			this.setState({
				filter: nextProps.filter,
				start: 0,
				data: []
			});
		}
	},

	looper: function(needClean) {
		// reset processing count
		this.setState({processingCount: 0});

		if(needClean) {
			this.setState({
				data: []
			});
		}
		var self = this;
		var currentUrl = this.state.src;
		$.ajax({
			url: this.state.src,
			dataType: 'json',
			cache: false
		})
		.done(function(data) {
			if(needClean) {
				self.setState({
					data: []
				});
			}
			var cnt = 1;
			if (data.results != null) {
				$.each(data.results, function(k,v) {
					if (currentUrl == self.state.src) {
		                self.repeat(v,cnt,data.results.length,data.found, currentUrl);
						cnt++;
					}
				});
			} else {
				jQuery('#loader').hide();
				jQuery('#fbc_loadMore').hide();
			}
		});
	},

	componentDidUpdate: function(prevProps,prevState) {
		if(this.state.type == 'library' && this.state.album.id != prevState.album.id) {
			jQuery('#fbc_loadMore').hide();
			jQuery('#loader').show();
			this.looper(true);
		} else if(this.state.type == 'search' && this.state.search != prevState.search) {
			jQuery('#fbc_loadMore').hide();
			jQuery('#loader').show();
			this.looper(true);
		} else if(this.state.type == 'filter' && this.state.filter != prevState.filter) {
			jQuery('#fbc_loadMore').hide();
			jQuery('#loader').show();
			this.looper(true);
		} else if(this.state.start > prevState.start) {
			jQuery('#fbc_loadMore').hide();
			jQuery('#loader').show();
			this.looper(false);
		}
	},

    render: function() {
        return (
			<div class="grid">
				<h1 className="text-center">{this.state.album.name}</h1>
				<ul className="attachments" id="__attachments-view-fbc">
	                <Images data={this.state.data} />

					<div id="fbc_loadMore_wrap">
						<button className="btn" id="fbc_loadMore" onClick={this.loadMore}>Load More</button>
					</div>
	            </ul>
			</div>
        );
    }
});
