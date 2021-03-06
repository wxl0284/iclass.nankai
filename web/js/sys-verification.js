/*
 * SYSUI-verification
 * 2018-12-01
 * 799129700@qq.com SYSHUXL-化海天堂
 * Reserved head available commercial use
 * Universal background system interface framework
 */
//合并
function extend(o, n, override) {
	for (var key in n) {
		if (n.hasOwnProperty(key) && (!o.hasOwnProperty(key) || override)) {
			o[key] = n[key];
		}
	}
	return o;
};
//简化document.getElementById方法
function ID$(i) {
	return document.getElementById(i)
};
//简化document.createElement方法
function LABEL$(i) {
	return document.createElement(i)
};
//简化document.getElementsByName方法
function NAME$(i) {
	return document.getElementsByName(i)
}
// 插件构造函数 - 返回数组结构
function SYSVerification(options) {
	this._initial(options);
};
//添加属性和方法
SYSVerification.prototype={
	constructor: this, //引用当前this
	//创建方法
	_initial: function(options) {
		//创建属性
		var par = {
			verification:'',//指定验证区域
			submit:'',//提交名
			icon:'&#xe617',
			Load:function(){},//载入数据方法
			Callback: function() {}//回调方法
			
		};
		this.par = extend(par, options, true);
		//判断是否存在class属性方法
		this.hasClass = function(elements, cName) {
			return !!elements.className.match(new RegExp("(\\s|^)" + cName + "(\\s|$)"));
		}
		//添加class属性方法
		this.addClass = function(elements, cName) {
			if (!this.hasClass(elements, cName)) {
				elements.className += " " + cName;
			};
		};
		//删除class属性方法 elements当前结构  cName类名
		this.removeClass = function(elements, cName) {
			if (this.hasClass(elements, cName)) {
				elements.className = elements.className.replace(new RegExp("(\\s|^)" + cName + "(\\s|$)"), " "); // replace方法是替换
			};
		};
		//根据class类名条件筛选结构
		this.getByClass = function(oParent, sClass) { //根据class获取元素
			var oReasult = [];
			var oEle = oParent.getElementsByTagName("*");
			for (i = 0; i < oEle.length; i++) {
				if (oEle[i].className == sClass) {
					oReasult.push(oEle[i]);
				}
			};
			return oReasult;
		}
		this.show(this.par);
	},
	//集合方法
	show:function(set){
		var _Method=this;
		var region = ID$(set.verification);
		var subname=ID$(set.submit);
		var conttext =NAME$("Required");//name集合
		var mobile_flag =_Method.isMobile(mobile_flag);
		_Method.ajaxObject(_Method);
		subname.onclick=function(event){	
		_Method.verificationMethod(region,conttext,subname,_Method,event);	
		}   
		_Method.Wordcount(_Method,conttext);
		if(mobile_flag){	
			   _Method.addClass(region,"mobile");
		}else{
			_Method.removeClass(region,"mobile");	
		}
		for(var i=0;i<conttext.length;i++){
			var textname = "不能为空！";
			conttext[i].onfocus=function(e){
				var evt = e || window.event;
				var tar = evt.target || evt.srcElement;
				if(tar.tagName.toLowerCase() == "input"){
				}
			};	
			//事件会在对象失去焦点时发生
			conttext[i].onblur=function(e){
				var evt = e || window.event;
				var tar = evt.target || evt.srcElement;
				if(tar.tagName.toLowerCase() == "input" || tar.tagName.toLowerCase() == "select"){
					var index = tar.selectedIndex; // 选中索引
					var Hints = tar.getAttribute('data-name');
					if(index!=null){
					    var selectname= tar.options[index].value ;	
					    if(selectname == "0") {
						_Method.newprompt(textname,Hints,_Method,tar);	 
					    }else{
						_Method.prompthtml(tar);	
					    }
				    }else if(tar.value != ""  ) {
				    	var promptname = tar.getAttribute('data-prompt');
						var zhi=tar.value;					
						_Method.formatmethod(conttext,Hints,_Method,tar,promptname,zhi);
				      //  _Method.prompthtml(tar);
				        
				    }else{
				        _Method.newprompt(textname,Hints,_Method,tar);	
				    }
				}
			};
		}
	},
	//判断是手机还是pc
	isMobile: function(mobile_flag) {
			var userAgentInfo = navigator.userAgent;
			var mobileAgents = ["Android", "iPhone", "SymbianOS", "Windows Phone", "iPad", "iPod"];
			var mobile_flag = false;
			//根据userAgent判断是否是手机
			for(var v = 0; v < mobileAgents.length; v++) {
				if(userAgentInfo.indexOf(mobileAgents[v]) > 0) {
			mobile_flag = true;
			break;
				}
			}
		var screen_width = window.screen.width;
		var screen_height = window.screen.height;
		//根据屏幕分辨率判断是否是手机
		if(screen_width < 500 && screen_height < 800) {
			mobile_flag = true;
		}
		return mobile_flag;
	},
	//声明ajax方法.，用于判断浏览器是否支持ajax
	ajaxObject: function(obj) {
		var xmlHttp;
		try {
			// Firefox, Opera 8.0+, Safari
			xmlHttp = new XMLHttpRequest();
		} catch (e) {
			// Internet Explorer
			try {
				xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try {
					xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					obj.PromptBox('您的浏览器不支持AJAX', 2);
					return false;
				}
			}
		}
		return xmlHttp;
	},
		//get请求
	ajaxGet: function(url, success) {
		var _Method = this;
		var ajax = _Method.ajaxObject();
		ajax.open("GET", url, true);
		ajax.onreadystatechange = function() {
			if (ajax.readyState == 4) {
				if (ajax.status == 200) {
					var json = ajax.responseText; //获取到json字符串，还需解析
					var jsonStr = JSON.parse(json); //将字符串转换为json数组
					success(jsonStr);
				} else {
					_Method.PromptBox("HTTP请求错误！错误码：" + ajax.status, 2);
				}
			}
		};
		ajax.send();
	},
	//Post请求
	ajaxPost: function(url,data) {
		var _Method = this;
		var ajax = _Method.ajaxObject();
		ajax.open("post", url, true);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		ajax.onreadystatechange = function() {
			if (ajax.readyState == 4) {
				if (ajax.status == 200) {
					_Method.PromptBox(ajax.responseText, 2);
				} else {
					_Method.PromptBox("HTTP请求错误！错误码：" + ajax.status, 2);
					return;
				}
			} else {
				//_this.PromptBox("请稍等...",3);
			}
			_Method.statusname(ajax.status, _Method);
		}
		typeof(data) != "undefined" ? ajax.send(data): '';
	},
	//提示
	statusname:function(status, set){
		if (status == 404) {
			set.PromptBox('页面已删除或不存在', 2);
		}else if(status ==500){			
			set.PromptBox('服务器出错，请稍后再试。', 2);
		}else if(status ==200){
			set.PromptBox('提交成功！', 2);
		}	
	},	
	//一个提示方法pc端
	newprompt:function(name,Hints,_Method,obj){
		var mobile_flag =_Method.isMobile(mobile_flag);
		var prompt =obj.parentNode.getElementsByTagName('span')[0];
		var newspan =LABEL$("span");
		if(mobile_flag){
			if(!prompt) {
				_Method.removeClass(obj.parentNode.appendChild(newspan),"prompt iconfont");	
				obj.parentNode.appendChild(newspan).className = "prompt mobile-prompt";
				newspan.innerHTML =Hints + name;
				return false; 
			} else {
				prompt.innerHTML =Hints + name;
			}	
		}else{
			if(!prompt) {
				obj.parentNode.appendChild(newspan).className = "prompt iconfont";
				newspan.innerHTML = _Method.par.icon + Hints + name;
				return false; 
			} else {
				prompt.innerHTML = _Method.par.icon + Hints + name;
			}	
		}
	},
	//清除提示信息
	prompthtml:function(obj){
		var prompt =obj.parentNode.getElementsByTagName('span')[0];
		if(prompt) {
			var prompthtml =obj.parentNode.removeChild(prompt);
		}
		return prompthtml;
	},
	//设置一个提示框，编辑提示框，texts为提示文本 ，time为显示时间秒单位
	PromptBox: function(texts, time) {
		var _Method = this;
		var b = document.body.querySelector(".box_Bullet");
		if (!b) {
			var box = document.createElement("div");
			document.body.appendChild(box).className = "box_Bullet";
			var boxcss = document.querySelector(".box_Bullet");
			var winWidth = window.innerWidth;
			document.body.appendChild(box).innerHTML = texts;
			var wblank = winWidth - boxcss.offsetWidth;
			box.style.cssText = "width:" + boxcss.offsetWidth + "px" + "; left:" + (wblank / 2) + "px" + ";" +
				"margin-top:" + (-boxcss.offsetHeight / 2) + "px";
			var int = setInterval(function() {
				time--;
				_Method.endclearInterval(time, box, int);
			}, 1000);
		}
	},
	endclearInterval: function(time, box, int) {
		time > 0 ? time-- : clearInterval(int);
		if (time == 0) {
			clearInterval(int);
			document.body.removeChild(box);
			return
		}
	},
	//判断方法
	verificationMethod:function(region,conttext,subname,_Method,event){
		var mobile_flag =_Method.isMobile(mobile_flag);
		var setvalue=[];
		mun=0;
		for(var i = 0; i < conttext.length; i++) {
			var verify=conttext[i].getAttribute('data-verify');		
			var textname = "不能为空！";
			if(verify === "verify") {	
				var obj=conttext[i];
				var Hints = conttext[i].getAttribute('data-name');
				var promptname = conttext[i].getAttribute('data-prompt');
				var selects = ID$("Competence_sort");
				var index = conttext[i].selectedIndex; // 选中索引
				if(index){
					var selectname= conttext[i].options[index].value ;	
				}
				if(conttext[i].value == ""  ) {
					_Method.newprompt(textname,Hints,_Method,obj);		
				}else if(conttext[i] == selects){	
					var selectname= conttext[i].options[index].value ;	
					if(selectname == "0") {
						_Method.newprompt(textname,Hints,_Method,selects);	 
						
					}else{
						_Method.prompthtml(selects);	
					}
				}else{
					_Method.prompthtml(obj);
				}
				mun++;
			}
				if(conttext[i].value!="" &&selectname!=0){
					if(promptname=="password"){
						var zhi=conttext[i].value;
					}
					_Method.formatmethod(conttext,Hints,_Method,obj,promptname,zhi);
					if(verify === "verify") {	
					setvalue.push(i);
					}
				}
		}
		_Method.submitoperate(setvalue,conttext,_Method,mun);
	},
	//格式验证方法
	formatmethod:function(conttext,Hints,_Method,obj,promptname,zhi){	
		if(promptname == "phone") {
			var expression = /^[1][3,4,5,7,8][0-9]{9}$/;
		} else if(promptname == "mailbox") {
			var expression = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
		} else if(promptname == "password" || promptname == "confirm" ) {
			var expression = /^[a-zA-Z]\w{5,17}$/;	
		}
		var v = obj.value;
		if(expression != null) {
			if(v!=""){
				if(!expression.test(zhi)) { 
					var textname = "格式输入有误。";
					_Method.newprompt(textname,Hints,_Method,obj);
					return false
				}
			};
			if(promptname == "confirm") {			
				if(v!=""){
				    if(zhi != v){
						var textname = "不一致,请从新输入。";
						_Method.newprompt(textname,Hints,_Method,obj);
						return false
					}else{
						_Method.prompthtml(obj);
					}
				}	
			}	
		}
	},
	submitoperate:function(setvalue,conttext,_Method,mun){
		if(setvalue.length>=mun){
			var formData = "";
			for(var i = 0; i < conttext.length; i++) {
			 	var keyvalue = conttext[i].getAttribute("data-value");
			 	var v = conttext[i].value;
			 	var muster = _Method.getByClass(conttext[i], 'radio');
			 	if(keyvalue!=null){	
			 		if(muster!=0){
				 		 for (var c = 0; c < muster.length; c++) {
							var checkedname = NAME$("radio")[c];
							if (checkedname.checked == true) {
								var v = NAME$("radio")[c].value;
							}
						}
			     	}
			 		if(keyvalue=="password"){
			 			formData += keyvalue + "=" + hex_sha1(v) + "&";
			 		}else{
			 			formData += keyvalue + "=" + v + "&";
			 		}
			 	}
			}
			_Method.par.Callback(_Method,formData);	
		}else{		
			return false	
		}
	},	
	//文本框字数限制设置
	Wordcount:function(_Method,conttext){
		for(var i = 0; i < conttext.length; i++) {
			var stint=conttext[i].getAttribute('data-stint');			
			if(stint=="Wordcount"){
				var obj=conttext[i];
				var span =LABEL$("span");
				var sl =conttext[i].getAttribute('size');
				if(sl){
					var size =parseInt(conttext[i].getAttribute('size'));
				}else{
					var size=20;
				}
				obj.parentNode.appendChild(span).className ="word_count";
				span.innerHTML = "剩余字数 :<em class='number'>" + size + "</em>字符";
				var prompt =_Method.getByClass(span,'number')[0];
				obj.onkeyup=function(event){
					_Method.Wordonkeyup(obj,size,_Method,prompt);
				}
			}
		}
	},
	Wordonkeyup:function(obj,size,_Method,prompt){
		if(obj.value.length > size) {			
			_Method.PromptBox("您输入的字数超过限制",2);
			obj.value = obj.value.substring(0, size);
			prompt.innerHTML = 0;
			return false;
		}else{
			var curr = size - obj.value.length; //减去 当前输入的	
			prompt.innerHTML = curr.toString();
			return true;
		}		
	}	
}