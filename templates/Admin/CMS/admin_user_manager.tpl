{config_load file=templates.ini section="common" scope="global"}

<div>
	<div id="tab_container" class="yui-navset">
	    <ul class="yui-nav">
	        <li {if !$tab || $tab=='active'}class="selected"{/if}><a href="#"><em>Users</em></a></li>
	    </ul>
	    <div class="yui-content">
	    	<div>
	    		<div class="search_bar">
	    			<div class="actions">
	    				<div style="float:left;"><a href="javascript:void(0);" onclick="user.edit(0);">Add New User</a>&nbsp;</div>
	    			</div>
	    			
					<div class="search_button"><a onclick="show_hide_el('sb_1');" href="javascript:void(0);">Search</a></div>
					<div style="clear: both"></div>
				</div>
				<div class="search_box" id="sb_1">
					<div style="float:left;">
					<form method="post" action="javascript:void(0);" id="user_filter_form">
					<table>
					<tr>						
						<td>
							Login
						</td>
						<td>
							<input type="text" name="login" value=""/>
						</td>
						<td>
							First name
						</td>
						<td>
							<input type="text" name="first_name" value=""/>
						</td>
						<td>
							Last name
						</td>
						<td>
							<input type="text" name="last_name" value=""/>
						</td>
						
					</tr>
					<tr>
						<td>
							Groups
						</td>
						<td>
							<select name="groups">
								<option value="">Any</option>
								<option value="admin">Admin</option>
								<option value="user">User</option>
							</select>
						</td>
                                                <td>
							E-mail
						</td>
						<td>
							<input type="text" name="email" value=""/>
						</td>
                                                <td>
                                                    &nbsp;
						</td>
                                                <td>
							&nbsp;
						</td>
					</tr>
					</table>
					</form>
					</div>
					<div class="go_button"><a onclick="user.search_go('user_filter_form');" href="javascript: void(0)">Go</a></div>
					<div style="clear: both"></div>
				</div>
				<div id="active_bpo-nav-1"></div>
	    		<div id="user_list"></div>
	    		<div>
		    		<div id="active_bpo-nav-2" style="width:300px;float:left;"></div>
					{*include file='Admin/helper_icons.tpl' view=$helper_icons_active*}
	    		</div>
	    	</div>
	    </div>
	</div>
</div>
{literal}
<style>
.yui-dt-hidden
{
display:none;
}
.yui-skin-sam .yui-dt table {
	width:100%;
}
.search_box {
display : none;
}
.search_bar {
background:transparent url(css/images/search_bar.png) repeat-x scroll center bottom;
border-bottom:1px solid #4E4E4E;
font-family:Trebuchet MS,Helvetica,san-serif;
font-size:11px;
line-height:21px;
}
.actions {
background:transparent url(css/images/add.png) no-repeat scroll left 1px;
float:left;
line-height:21px;
padding:0 20px;
width:auto;
}
.search_button {
background:transparent url(css/images/search_button.png) no-repeat scroll 0 0;
float:right;
line-height:21px;
text-align:center;
width:97px;
}

.search_button a, #search_button a:visited {
background:transparent url(css/images/magnifier.png) no-repeat scroll left 1px;
color:#FFFFFF;
line-height:21px;
padding-left:20px;
}

.go_button {
background:#4E4E4E none repeat scroll 0 0;
float:right;
font-size:11px;
line-height:21px;
text-align:center;
width:80px;
}

.go_button a, .go_button a:visited {
background:transparent url(css/images/accept.png) no-repeat scroll left 1px;
color:#FFFFFF;
line-height:21px;
padding-left:20px;
}
.yui-skin-sam .mask {
z-index:1000;
}

</style>
{/literal}

<script type="text/javascript">
{literal}

function show_hide_el(id)
{
        if(document.getElementById(id).style.display == 'block')
        {
                document.getElementById(id).style.display = 'none';
        }
        else
        {
                document.getElementById(id).style.display = 'block';
        }
}

YAHOO.util.Event.onDOMReady(user_list_init);


var open_close = false;
function show_sb(id)
{
	if(document.getElementById(id).style.display == 'none')
	{
		document.getElementById(id).style.display = 'block';
	}
	else
	{
		document.getElementById(id).style.display = 'none';
	}
	
	return false;
	if(!open_close)
	{
		document.getElementById('sb_1').style.display = 'block';
		open_close = true;
	}
	else
	{
		document.getElementById('sb_1').style.display = 'none';
		open_close = false;
	}
}
var tab_view;

var user_list;
var user_paginator;
var user_ds;


function user_list_init()
{
	tab_view = new YAHOO.widget.TabView("tab_container");
	tab_view.addListener('beforeActiveTabChange',function (e){
		//alert(e);
		//alert(e.newValue.get('href'));
		document.location = e.newValue.get('href');
		return false;
	});
	{/literal}
	var user_column_defs = {$user_column_defs};
	
	{literal}
	user_ds = new YAHOO.util.DataSource(ajax_prefix + 'ajax_user_list.html',{connMethodPost:true});
	user_ds.responseType = YAHOO.util.DataSource.TYPE_JSON;
	user_ds.responseSchema = {
		resultsList: "records",
		fields: {/literal}{$user_source_fields},{literal}
		metaFields: {totalRecords: "totalRecords" }
	};

	user_paginator = new YAHOO.widget.Paginator({
									rowsPerPage: 25 ,
									containers : ["active_bpo-nav-1","active_bpo-nav-2"], 
									template: YAHOO.widget.Paginator.TEMPLATE_ROWS_PER_PAGE,
	                				rowsPerPageOptions: [5,10,25,50,100],
	                				pageLinks: 5  });

	var user_data_config = {
		initialRequest: 'asd',
		dynamicData: true,
		paginator: user_paginator,
		generateRequest :user.generate_request,
		sortedBy : {key:"login", dir:'asc'}
		//formatRow : bpo.format_row
	};

	user_list = new YAHOO.widget.DataTable('user_list', user_column_defs, user_ds, user_data_config);

	user_list.handleDataReturnPayload = function(oRequest, oResponse, oPayload)
	{
        oPayload.totalRecords = oResponse.meta.totalRecords;
        return oPayload;
    }
}
{/literal}
</script>