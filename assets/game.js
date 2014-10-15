

(function ($) {
	$.fn.getCursorPosition = function () {
		var input = this.get(0);
		if (!input) return; // No (input) element found
		if ('selectionStart' in input) {
			// Standard-compliant browsers
			return input.selectionStart;
		} else if (document.selection) {
			// IE
			input.focus();
			var sel = document.selection.createRange();
			var selLen = document.selection.createRange().text.length;
			sel.moveStart('character', -input.value.length);
			return sel.text.length - selLen;
		}
	}

	$.fn.selectRange = function (start, end) {
		if (!end) end = start;
		return this.each(function () {
			if (this.setSelectionRange) {
				this.focus();
				this.setSelectionRange(start, end);
			} else if (this.createTextRange) {
				var range = this.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		});
	};
})(jQuery);

$(function () {
    
    function Login() {
        var self = this;
        
        self.dialog = $("#login-dialog");
        self.nameInput = $("#login-name");
        self.submitButton = $("#login-button");
        self.errorField = $("#login-errors");
        
        self.open = false;
        
        self.submitButton.click(submit);
        self.nameInput.keypress(function(e) {
            if (e.which == 13) {
                submit();
            }
        });
        
        function submit(){
            self.submitButton.addClass("disabled");
            self.submitButton.text("Verifying..");
            
            var name = self.nameInput.val();
            $.ajax({url:"login.php", type:"POST", data:{name:name}, dataType:"json"})
                .done(function(result) {
                    self.errorField.empty();
                    if(result) {
                        if (result.success) {
                            self.dialog.modal("hide");
                        }
                        else {
                            var errors = "";    
                            result.errors.forEach(function(error) {
                                if (errors != "")
                                    errors += "\n";
                                    
                                errors += error;
                            });
                        
                            self.errorField.text(errors);
                        }
                    }
                })
                .fail(function(result) {
                    debugger
                    console.log("login error");
                })
                .always(function() {
                    self.submitButton.removeClass("disabled");
                    self.submitButton.text("Login");
                });
        }
        
        self.show = function() {
            self.dialog.modal("show");
            self.open = true;
        }
        
        self.hide = function() {
            self.dialog.modal("hide");
            self.open = false;
        }
        
        // initially show. will close on next sync if logged in already.
        self.dialog.modal("show");
        self.open = true;
    }
    
	function Client() {

		///=========================================
		///=========== Self Relationship
		///=========================================

		var self = this;
        
        
        ///=========================================
		///=========== Data
		///=========================================
        
        self.login = new Login();
        

		///=========================================
		///=========== JQuery Cache
		///=========================================

		self.log = $("#chatroom-log");
		self.input = $("#chatroom-input");
		self.sendButton = $("#chatroom-send-button");
		self.occupants = $("#chatroom-occupants");
        self.logout = $("#logout-link");


		///=========================================
		///=========== State Variables
		///=========================================

		var sendInProgress = false;


		///=========================================
		///=========== Game Log Actions
		///=========================================

		self.write = function (msg) {
            
            // Check if scrolled to bottom of log.
            // Got -12 through experimentation to find the bottom scroll value.
            // There should be a more robust way than this, but this works for now.
            var atBottom = (self.log.scrollTop() + self.log.height() - self.log[0].scrollHeight == -12);
            
			var result = self.log.val();
			if (result.length != 0) result += "\n";
			result += msg;
            
            // keep log from going too large. This will likely cut off
            // part of the oldest log entry, but that entry is about to
            // vanish off the face of the earth anyways.
            if (result.length > 5000) {
                result = result.slice(-5000);
            }

            // refresh log
			self.log.val(result);
            
            // Keep at bottom if was at bottom.
            if (atBottom)
                self.log.scrollTop(self.log[0].scrollHeight);
		};

		///=========================================
		///=========== Input Field Actions
		///=========================================

		function sendCommand() {
			var msg = self.input.val();
			if (msg.length == 0 || sendInProgress) return;

			sendInProgress = true;
			self.sendButton.addClass("disabled");
			self.sendButton.text("Submitting..");

			self.input.val("");
            
            $.ajax({url:"send.php", type:"POST", data:{message:msg}, dataType:"json"})
                .done(function(result){
                    if(result && result.errors && !result.success) {
                        result.errors.forEach(function(error) {
                            self.write(error);
                        });
                    }
                })
                .fail(function(result) {
                })
                .always(function(result) {
                    self.sendButton.removeClass("disabled");
                    self.sendButton.text("Send");
                    sendInProgress = false;
                });

            /*
			setTimeout(function () {
				self.write(msg);
				self.sendButton.removeClass("disabled");
				self.sendButton.text("Send");
				sendInProgress = false;
			}, Math.random() * 400 + 200);
            */

		}
        
        ///=========================================
		///=========== Auto Sync
		///=========================================
        
        setInterval(function() {
            $.ajax({url:"remote.php", dataType:"json"})
                .done(function(result) {
                    $("#num-online").text(result.online);
                    $("#num-registered").text(result.registered);
                    //$("#num-bots").text(result.bots);
                    $("#display-name").text(result.name);
                    $("#num-room").text(result.in_room.length);
                    
                    updateOccupants(result.in_room);
                    insertMessages(result.messages);
                    updateLoginStatus(result.name);
                });
        }, 250);
        
        // update in room list
        function updateOccupants(names) {
            self.occupants.empty();
            var html = "";
            
            names.forEach(function(name) {
                html += "<li class='list-group-item'>" + name + "</li>";
            });
            
            self.occupants.html(html);
        }
        
        // add new messages to game log
        function insertMessages(messages) {
            messages.forEach(function(msg) {
                switch (msg.type) {
                    case "ping":
                        // do nothing for now
                        break;
                    case "text":
                    case "error":
                        self.write(msg.text);
                        break;
                }
            });
        }

        function updateLoginStatus(name) {
            if (name) {
                if (self.login.open) {
                    $.ajax({url:"send.php", type:"POST", data:{message:"look"}});
                }
                self.login.hide();
            }
            else 
                self.login.show();
        }

		///=========================================
		///=========== User Events
		///=========================================

        // click person's name
		self.occupants.on("click", "li", function (e) {
			var name = $(e.currentTarget).text();
			var msg = "tell " + name + " ";
			self.input.val(msg);
			self.input.focus();
			self.input.selectRange(msg.length);
		});

		// input + enter
		self.input.keyup(function (e) {
			if (e.keyCode == 13) sendCommand();
		});

		// input + send button
		self.sendButton.click(sendCommand);
        
        // logout link
        self.logout.click(function() {
            $.ajax({url:"logout.php", type:"POST"});
        });

	}

	// Initialize
	var client = new Client();
});

