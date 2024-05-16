<?php
/** @var SeattleMakers\Discord\OAuth_URL $oauth */
/** @var string $nick */
/** @var SeattleMakers\Roles_View $roles */
?>

<div class="discord-steps">
    <div class="step active">
        <h3 class="title">Link</h3>
        <h4>your account</h4>
        <p>To join our private Discord community server, link your Discord account with Seattle Makers.</p>
        <p>This will redirect you to Discord, where you can log in with an existing Discord account or create a new one, and authorize the Seattle Makers link.</p>
        <div class="actions">
            <a class="grve-btn grve-btn-medium grve-extra-round grve-bg-primary-1 grve-bg-hover-black"
               href="<?php echo $oauth->url ?>">Link Discord account</a>
        </div>
    </div>
    <div class="step">
        <h3 class="title">Join</h3>
        <h4>our server</h4>
        <p>
            After linking your Discord account, we'll add you our Discord community server automatically.
        </p>
        <p>Your server nickname will be <strong><?php echo $nick ?></strong>.</p>
        <p><em>If that looks incorrect, verify that your information is up to date in <a href="/paupress/profile">your
                    profile</a></em>.</p>
    </div>
    <div class="step">
        <h3 class="title">Claim</h3>
        <h4>your roles</h4>
        <p>
            Roles in Discord grant special permissions based on your membership status.
        </p>
        <?php if (count($roles->eligible()) > 0) { ?>
            <p>You'll get the following roles:</p>

            <ul class="roles">
                <?php foreach ($roles->eligible() as $role) { ?>
                    <li><?php echo($role->name) ?></li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <p><strong>It looks like you're not eligible for any special roles.</strong></p>
        <?php } ?>
        <p><em>If that looks incorrect, please contact us to review your profile.</em></p>
    </div>
</div>