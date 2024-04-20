<?php
/** @var $user */
/** @var $server_id */
?>
<iframe src="https://discordapp.com/widget?id=<?php echo $server_id ?>&theme=light" width="350" height="500"
        allowtransparency="true" frameborder="0"
        sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
<p>You're connected as <?php echo $user->user->username ?>!</p>
<pre>
<?php print_r($user); ?>
</pre>
