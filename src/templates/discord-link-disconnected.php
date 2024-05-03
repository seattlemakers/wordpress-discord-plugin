<?php
/** @var SeattleMakers\Discord\OAuth_URL $oauth */
/** @var string $nick */
/** @var string $roles */
?>

<p>In order to join our Discord server, you'll have to link your Discord and Seattle Makers website accounts.</p>
<p>This is necessary in order to:</p>
<ul>
    <li>Grant these Discord server roles based on your membership: <strong><?php echo join(", ", $roles) ?></strong>
    </li>
    <li>Set your server nickname to meet our server rules: "<strong><?php echo $nick ?></strong>"</li>
</ul>
<p>If any of that looks incorrect, verify that your information is up to date in <a href="/paupress/profile">your
        profile</a>.</p>
<p>If that sounds good, then go ahead and <a href="<?php echo $oauth->url ?>">link your account to Discord</a>.</p>
