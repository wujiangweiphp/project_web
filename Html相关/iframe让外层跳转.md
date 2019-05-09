
```html
<html>
	<head>
		<title>
			跳转测试
		</title>
		<style>

		</style>
	</head>
	<body>
        <div id="admin">
        	<iframe id="ff" src="http://www.baidu.com" width="500px" height="300px" frameborder="0"></iframe>
        </div>
        <script>
        var iframe = document.getElementById('ff');
        setTimeout(function(){
        	iframe.src="http://jingdong.com"
        },1000)
        var NUM = 0
		iframe.onload = function() {
		    NUM++
		    if (NUM == 2) {
		    	window.location.href = iframe.src;
		    }
		}
		</script>
	</body>
</html>
```

