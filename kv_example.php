<!doctype html>
<html>
  <head>
  <title>PHP PDO key/value abstraction layer</title>
  <style>
  html{
    font:12px/24px verdana;
  }
  h1{
    font:16px/32px arial;
    font-weight:bold;
    margin-top: 40px;
  }
  code{
    border: 1px solid black;
    padding: 10px;
    margin: 10px;
    white-space:pre;
    display:block;
  }
  
  </style>
  </head>
 <body>
 <h1>PHP PDO key/value abstraction layer</h1>
 <a href="kv.phps">source</a>, <a href="/kv-phpdoc">phpdoc</a>
 <?php
require_once('kv.php');
?>
<h1>create test database</h1>
<code>
require_once('kv.php');
$ds = new kvstore(new PDO($dsn,$user,$pass),'table-0');
$ds->dropTable();
$ds->createTable();
$ds->errorExceptions();
//$ds->errorSilent();
//$ds->errorWarning();
</code>
<?php
$ds = new kvstore(new PDO('mysql:host=db62.1and1.pl;dbname=db339205542','dbo339205542','qazwsxedcrfv'));
$ds->dropTable();
$ds->createTable();
$ds->errorExceptions();
?>


<h1>insert sample value and retrive it</h1>
<code>
$ds->set('key', 'value');
$v = $ds->get('key');
</code>

<?php
$ds->set('key', 'value');
$v = $ds->get('key');
var_dump($v);
?>
<h1>insert array of values</h1>
<code>
$mkv = array(
   'usr:0001' => 'First user',
   'usr:0002' => 'Second user', 
   'usr:0003' => 'Third user' 
);
$ds->mset($mkv);
</code>

<?php
$mkv = array(
  'usr:0001' => 'First user',
  'usr:0002' => 'Second user', 
  'usr:0003' => 'Third user' 
);
$ds->mset($mkv);
?>

<h1>Retrive as array [ [k,v],[k,v] ] </h1>
<code>
$x = $ds->mget_assoc(array_keys($mkv));
</code>
<?php
$x = $ds->mget_assoc(array_keys($mkv));
?><pre><?php print_r($x); ?></pre>

<h1>as array [[k=>v],[k=>v]]</h1>
<code>
$x = $ds->mget(array_keys($mkv));
</code>
<?php
$x = $ds->mget(array_keys($mkv));
?>
<pre><?php print_r($x); ?></pre>


<h1>increment test:</h1>
<code>
$x = $ds->incr('counter1');
$x = $ds->incr('counter2');
$x = $ds->incr('counter2');
$x = $ds->incr('counter2');
</code>


<?php
$x = $ds->incr('counter1');
$x = $ds->incr('counter2');
$x = $ds->incr('counter2');
$x = $ds->incr('counter2');
var_dump($x);
?>

<h1>get array of keys via mget after incrementation</h1>
<code>
$x = $ds->mget(array('counter1', 'counter2'));
</code>


<?php
$x = $ds->mget(array('counter1', 'counter2'));
?><pre><?php print_r($x); ?></pre>

<h1>push values</h1>
<code>
$ds->rpush('list', 'a');
$ds->rpush('list', 'b');
$ds->lpush('list', 'c');
$ds->rpush('list', array('e'=>'f'));

print_r($ds->get('list'));
</code>


<?php
$ds->rpush('list', 'a');

print_r($ds->get('list'));echo '<br />';

$ds->rpush('list', 'b');

print_r($ds->get('list'));echo '<br />';

$ds->lpush('list', 'c');

print_r($ds->get('list'));echo '<br />';

$ds->rpush('list', array('e'=>'f'));
print_r($ds->get('list'));echo '<br />';
?>

<h1>get results like key</h1>
<code>
$x = $ds->getLike('usr%');
</code>
<?php
$x = $ds->getLike('usr%');
?><pre><?php print_r($x); ?></pre>

<h1>get object as JSON</h1>
<code>
$x = $ds->getJSON('list');
</code>
<?php
$x = $ds->getJSON('list');
?><pre><?php print_r($x); ?></pre>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-4497317-8']);
  _gaq.push(['_setDomainName', 'rashell.pl']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</body></html>





