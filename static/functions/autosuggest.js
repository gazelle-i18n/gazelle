"use strict";
var autocomplete = {
	id: "",
	timer: null,
	input: null,
	value: "",
	list: null,
	cache: [],
	key: function (id,e) {
		clearTimeout(this.timer);
		
		this.setup(id);
		
		var keycode = (e.which === null)?e.keyCode:e.which, 
			time=220;
		
		console.log(keycode);
		switch(keycode) {
			case 8: //backspace
				time = 600;
				this.list.style.visibility = "hidden";
				this.timer = setTimeout("autocomplete.get('"+id+"');",time);
				break;
			case 27:
			case 38: //esc
				e.preventDefault();
				this.list.style.visibility = "hidden";
				break;
			case 38: //up
				e.preventDefault();
			
				break;
			case 40: //down
				e.preventDefault();
			
				break;
			default:
				if (this.input.value.length < 1) {
					this.list.style.visibility = "hidden";
					return;
				}
				
				if (this.value === this.input.value) {
					return;
				}
			
				this.value = this.input.value;
				this.timer = setTimeout("autocomplete.get('"+id+"');",time);
		}
	},
	get: function (id) {
		if (typeof this.cache[id+this.input.value] === 'object') {
			this.display(this.cache[id+this.input.value]);
			return;
		}
		
		ajax.get(id+'.php?action=autocomplete&name='+this.input.value,function(jstr){
			data = json.decode(jstr);
			autocomplete.cache[id+data[0]] = data;
			autocomplete.display(data);
		});
	},
	display: function (data) {
		this.list.innerHTML = '';
		for (var i=0,il=data[1].length;i<il;++i) {
			var li = document.createElement('li');
			li.innerHTML = data[1][i];
			this.list.appendChild(li);
		}
		var t = offset(autocomplete.input);
		this.list.style.top = t[0]+'px';
		this.list.style.left = t[1]+'px';
		this.list.style.visibility = 'visible';
	},
	setup: function (id) {
		if (id !== null && this.input === null) {
			this.input = document.getElementById(id+"search");
		}
		if (this.list === null) {
			this.list = document.getElementById('autocomplete');
		}
	},
	focus: function () {
		this.setup();
		this.list.style.visibility = "visible";
	},
	blur: function () {
		this.setup();
		this.list.style.visibility = "none";
	}
};
