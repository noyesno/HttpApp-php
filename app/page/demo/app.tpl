<html>
<head>
<title>nPhp Framework - http://noyesno.net</title>
</head>
<body>
<h1>nPhp Framework</h1>

<div style="text-align:center">
  <a href="http://noyesno.net/">非是非</a> (Rev 0.1)
</div>

<fieldset><legend>$env[]</legend>
<pre>
{var_export($env)}
</pre>
</fieldset>

<fieldset><legend>Smarty</legend>
<pre>
$this->view->assign('name','value');

$env.request.root
$env.app.base
$env.app.argc
$env.app.argv
$env.app.args[]
$env.tpl.theme
$env.tpl.style

$model->name()
$helper->url()
$view.name
</pre>
</fieldset>

<fieldset><legend>Cache</legend>
<ul>
<li>Server Side Cache</li>
<li>Client Side Cache</li>
</ul>
</fieldset>

<fieldset><legend>Authentation</legend>
<form method="post" action="{$env.app.base}/auth">
<label>Pass Code:</label><input type="password" name="passcode"/> (= 9999)

<p>
<button type="submit">Login</button>
</p>
</form>

</fieldset>

<fieldset><legend>Form Validation</legend>
<form method="post">
<label>EMail:</label><input type="email" name="email"/><br/>
<label>Age:</label><input type="number" name="age" max="81" min="18"/> (Please input number in range [18,81])

<p>
<button type="submit">Submit</button>
</p>
</form>
</fieldset>

<fieldset><legend>Form Validation</legend>
Paginator
</fieldset>


<fieldset><legend>Resource</legend>
<ul>
<li><a href="http://sae.sina.com.cn/?m=devcenter&catId=147">Sina App Engine 公共资源</a> (CDN)</li>
</ul>
</fieldset>

<hr/>

</body>
</html>

