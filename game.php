<html>

	<head>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css"></link>
		<link rel="stylesheet" href="/assets/game.css"></link>
	</head>

	<body>
        
        <div id="login-dialog" class="modal fade" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title text-center">Welcome to Generic MUD!</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xs-offset-2 col-xs-8 text-center">
                                <label for="login-name">Please enter your adventurer name</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-offset-2 col-xs-8">
                                <input id="login-name" type="text" class="form-control text-center" placeholder="an adventurous name with 3-12 non-space letters"></input>
                            </div>
                        </div>
                        <div class="row text-center" style="padding:20px;">
                            <div id="login-errors" class="col-xs-offset-2 col-xs-8">
                            </div>
                        </div>
                        <div class="row" style="padding-top:20px; padding-bottom:20px;">
                            <div class="col-xs-offset-4 col-xs-4">
                                <button type="button" id="login-button" class="btn btn-info form-control">Login</button>
                            </div>
                        </div>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        
		<div class="container-fluid">
			<div class="row" id="page-top-bar">
				<div class="col-xs-2">Online: <span id="num-online"></span></div>
				<div class="col-xs-2">Registered: <span id="num-registered"></span></div>
				<!--<div class="col-xs-3">Bots Online: <span id="num-bots"></span></div>-->
				<div class="col-xs-offset-3 col-xs-4">Your Name: <span id="display-name"></span></div>
				<div class="col-xs-1"><a id="logout-link">Logout</a></div>
			</div>
			<div class="row page-divider"></div>
			<div class="row" id="main-content">
				<div class="col-xs-8">
					<div class="row">
						<div class="col-xs-12">
							 <h3>Game Log</h3>

						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<div id="chatroom-log-container" class="">
								<textarea id="chatroom-log" class="form-control" readonly="readonly"></textarea>
							</div>
						</div>
					</div>
					<div class="row" id="chatroom-input-spacer"></div>
					<div class="row">
						<div class="col-xs-12">
							<div id="chatroom-input-container">
								<div class="input-group">
									<input id="chatroom-input" type="text" class="form-control"></input> <span class="input-group-btn">
										<button id="chatroom-send-button" class="btn btn-default" type="button">Send</button>
									</span>

								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xs-4">
					 <h3>People: <span id="num-room"></span></h3>

					<div id="chatroom-occupants-container" class="panel">
						<ul id="chatroom-occupants" class="list-group">
							<li>Adam</li>
							<li>Lenny</li>
							<li>Arnold</li>
							<li>Jacob</li>
							<li>Jenny</li>
							<li>William</li>
						</ul>
					</div>
				</div>
			</div>
			<div class="row page-divider"></div>
			<div class="row" id="page-footer">
				<div class="col-xs-12">
					An Interview Test For And By Desmond Gee
				</div>
			</div>
		</div>
		
		<script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
		<script src="/assets/game.js"></script>
		

	
	</body>
	
</html>
	
